<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLogger
{
    /**
     * Log an action by staff.
     *
     * @param string $action        The action type (e.g., 'booking_created').
     * @param Model|string|null $target  The target model affected (e.g., Booking instance) or string label.
     * @param array|null $oldValues  Old values (before update), optional.
     * @param array|null $newValues  New values (after update), optional.
     * @param string|null $description Additional notes.
     */
    public static function log(
        string $action,
        Model|string|null $target = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $description = null,
        $staffModel = null   // optional, explicitly pass staff
    ): void {
        $staff = $staffModel ?? Auth::guard('staff')->user();

        AuditLog::create([
            'staff_id'   => $staff?->id,
            'role'       => $staff?->role ?? 'staff',
            'action'     => $action,
            'target_type'=> $target instanceof Model ? class_basename($target) : (is_string($target) ? $target : null),
            'target_id'  => $target instanceof Model ? $target->id : null,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'description'=> $description,
            'ip_address' => request()->ip(),
            'user_agent' => request()->header('User-Agent'),
        ]);
    }
}
