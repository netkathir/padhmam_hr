<?php

namespace App\Console\Commands;

use App\Services\EmployeeShifts\EmployeeShiftStatusService;
use Illuminate\Console\Command;

/**
 * Daily Scheduled→Active→Completed status sync for Employee Shift
 * Assignments (spec section 35). Idempotent — safe to run multiple times
 * a day, and resolution logic already works correctly even if a run is
 * missed, since it derives validity from effective dates rather than the
 * stored status column.
 */
class SyncEmployeeShiftAssignmentStatuses extends Command
{
    protected $signature = 'employee-shifts:sync-statuses';

    protected $description = 'Recompute Scheduled/Active/Completed status and is_current for all Employee Shift Assignments.';

    public function handle(EmployeeShiftStatusService $statusService): int
    {
        $result = $statusService->syncAll();

        $this->info("Employee Shift Assignment status sync complete: {$result['updated']} updated, {$result['activated']} newly activated, {$result['completed']} newly completed.");

        return self::SUCCESS;
    }
}
