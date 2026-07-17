<?php

namespace App\Http\Requests\Shifts;

use App\Models\Shift;
use App\Services\BranchContext;
use App\Services\Shifts\ShiftTimingService;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreShiftRequest extends FormRequest
{
    public function __construct(private readonly ShiftTimingService $timingService)
    {
        parent::__construct();
    }

    public function authorize(): bool
    {
        return $this->user()?->can('create', Shift::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'shift_code' => $this->filled('shift_code') ? strtoupper(trim((string) $this->input('shift_code'))) : $this->input('shift_code'),
            'shift_name' => $this->filled('shift_name') ? trim((string) $this->input('shift_name')) : $this->input('shift_name'),
            'short_name' => $this->filled('short_name') ? trim((string) $this->input('short_name')) : null,
            'color_code' => $this->filled('color_code') ? strtoupper(trim((string) $this->input('color_code'))) : null,
            'overtime_applicable' => $this->boolean('overtime_applicable'),
            'applicable_days' => array_values(array_unique(array_map('strtoupper', (array) $this->input('applicable_days', [])))),
            'employee_type_ids' => array_values(array_filter((array) $this->input('employee_type_ids', []))),
        ]);
    }

    public function rules(): array
    {
        $branchId = app(BranchContext::class)->currentBranchId();

        return [
            'shift_code' => [
                'required', 'string', 'max:50', 'regex:/^[A-Z0-9_-]+$/',
                Rule::unique('shifts', 'shift_code')->where('branch_id', $branchId),
            ],
            'shift_name' => [
                'required', 'string', 'max:150',
                Rule::unique('shifts', 'shift_name')->where('branch_id', $branchId),
            ],
            'short_name' => ['nullable', 'string', 'max:50'],
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
            'employee_type_ids' => ['present', 'array'],
            'employee_type_ids.*' => [Rule::exists('employee_types', 'id')->where('status', 'active')],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'color_code' => ['nullable', 'string', 'regex:/^#[0-9A-F]{6}$/'],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::in([Shift::STATUS_DRAFT, Shift::STATUS_INACTIVE])],
        ];
    }

    public function withValidator(Validator $validator): void
    {
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

            if ($this->boolean('overtime_applicable') && ! $this->filled('overtime_start_after_minutes')) {
                // Zero is a valid explicit value; only block when the field was left entirely empty.
                if ($this->input('overtime_start_after_minutes') === null) {
                    $validator->errors()->add('overtime_start_after_minutes', 'Enter the number of minutes after Shift end before overtime begins, or 0 to start immediately.');
                }
            }

            if ($this->input('status') === Shift::STATUS_ACTIVE && empty($this->input('applicable_days'))) {
                $validator->errors()->add('applicable_days', 'At least one applicable day must be selected for an Active Shift.');
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
