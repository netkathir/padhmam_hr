<?php

namespace App\Http\Requests\Shifts;

use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use App\Services\Shifts\ShiftTimingService;

class UpdateShiftRequest extends FormRequest
{
    public function __construct(private readonly ShiftTimingService $timingService)
    {
        parent::__construct();
    }

    public function authorize(): bool
    {
        /** @var Shift|null $shift */
        $shift = $this->route('shift');

        return $shift ? $this->user()?->can('update', $shift) ?? false : false;
    }

    protected function prepareForValidation(): void
    {
        /** @var Shift $shift */
        $shift = $this->route('shift');

        $merge = [
            'shift_code' => $this->filled('shift_code') ? strtoupper(trim((string) $this->input('shift_code'))) : $this->input('shift_code'),
            'shift_name' => $this->filled('shift_name') ? trim((string) $this->input('shift_name')) : $this->input('shift_name'),
            'short_name' => $this->filled('short_name') ? trim((string) $this->input('short_name')) : null,
            'color_code' => $this->filled('color_code') ? strtoupper(trim((string) $this->input('color_code'))) : null,
            'employee_type_ids' => array_values(array_filter((array) $this->input('employee_type_ids', []))),
        ];

        if (! $shift->hasOperationalUsage()) {
            $merge['overtime_applicable'] = $this->boolean('overtime_applicable');
            $merge['applicable_days'] = array_values(array_unique(array_map('strtoupper', (array) $this->input('applicable_days', []))));
        }

        $this->merge($merge);
    }

    public function rules(): array
    {
        /** @var Shift $shift */
        $shift = $this->route('shift');
        $hasUsage = $shift->hasOperationalUsage();

        $rules = [
            'shift_code' => [
                'required', 'string', 'max:50', 'regex:/^[A-Z0-9_-]+$/',
                Rule::unique('shifts', 'shift_code')->where('branch_id', $shift->branch_id)->ignore($shift->id),
            ],
            'shift_name' => [
                'required', 'string', 'max:150',
                Rule::unique('shifts', 'shift_name')->where('branch_id', $shift->branch_id)->ignore($shift->id),
            ],
            'short_name' => ['nullable', 'string', 'max:50'],
            'employee_type_ids' => ['present', 'array'],
            'employee_type_ids.*' => [Rule::exists('employee_types', 'id')->where('status', 'active')],
            'color_code' => ['nullable', 'string', 'regex:/^#[0-9A-F]{6}$/'],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];

        if ($hasUsage) {
            $rules['effective_to'] = ['nullable', 'date'];

            return $rules;
        }

        return [
            ...$rules,
            'shift_type' => ['required', Rule::in([Shift::TYPE_FIXED, Shift::TYPE_ROTATIONAL])],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'break_duration_minutes' => ['required', 'integer', 'min:0'],
            'early_entry_allowed_minutes' => ['required', 'integer', 'min:0', 'max:'.config('hrms.shift_grace_minutes_max')],
            'late_entry_grace_minutes' => ['required', 'integer', 'min:0', 'max:'.config('hrms.shift_grace_minutes_max')],
            'early_exit_grace_minutes' => ['required', 'integer', 'min:0', 'max:'.config('hrms.shift_grace_minutes_max')],
            'late_exit_allowed_minutes' => ['required', 'integer', 'min:0', 'max:'.config('hrms.shift_grace_minutes_max')],
            'minimum_half_day_minutes' => ['nullable', 'integer', 'min:1'],
            'minimum_full_day_minutes' => ['nullable', 'integer', 'min:1'],
            'overtime_applicable' => ['required', 'boolean'],
            'overtime_start_after_minutes' => ['nullable', 'integer', 'min:0'],
            'applicable_days' => ['present', 'array'],
            'applicable_days.*' => [Rule::in(Shift::DAY_CODES)],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        /** @var Shift $shift */
        $shift = $this->route('shift');

        if ($shift->hasOperationalUsage()) {
            $validator->after(function (Validator $validator) use ($shift): void {
                $value = $this->input('effective_to');

                if ($value && $shift->effective_from && Carbon::parse($value)->lt($shift->effective_from)) {
                    $validator->errors()->add('effective_to', 'The effective-to date must not be earlier than the effective-from date.');
                }
            });

            return;
        }

        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->hasAny(['start_time', 'end_time'])) {
                return;
            }

            $start = $this->filled('start_time') ? Carbon::createFromFormat('H:i', $this->input('start_time')) : null;
            $end = $this->filled('end_time') ? Carbon::createFromFormat('H:i', $this->input('end_time')) : null;

            if (! $start || ! $end) {
                return;
            }

            if ($start->format('H:i') === $end->format('H:i')) {
                $validator->errors()->add('end_time', 'The end time must be different from the start time.');

                return;
            }

            $gross = $this->timingService->grossMinutes($start, $end);
            $break = (int) $this->input('break_duration_minutes', 0);

            if ($break >= $gross) {
                $validator->errors()->add('break_duration_minutes', 'The break duration must be less than the gross shift duration.');

                return;
            }

            $half = $this->input('minimum_half_day_minutes');
            $full = $this->input('minimum_full_day_minutes');
            $scheduled = $this->timingService->scheduledWorkMinutes($gross, $break);

            if ($half && $full && (int) $half >= (int) $full) {
                $validator->errors()->add('minimum_full_day_minutes', 'The full-day threshold must be greater than the half-day threshold.');
            }

            if ($full && (int) $full > $scheduled) {
                $validator->errors()->add('minimum_full_day_minutes', 'The full-day threshold must not exceed the scheduled work duration.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'shift_code.regex' => 'The shift code may only contain letters, numbers, hyphens, and underscores.',
            'shift_code.unique' => 'This shift code is already used within the active branch.',
            'shift_name.unique' => 'This shift name is already used within the active branch.',
            'start_time.date_format' => 'Enter the start time in HH:MM format.',
            'end_time.date_format' => 'Enter the end time in HH:MM format.',
            'employee_type_ids.*.exists' => 'Select only active Employee Types.',
            'effective_to.after_or_equal' => 'The effective-to date must not be earlier than the effective-from date.',
            'color_code.regex' => 'Enter a valid hex colour code, e.g. #1F6FEB.',
        ];
    }
}
