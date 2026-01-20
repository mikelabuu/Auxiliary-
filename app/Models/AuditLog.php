<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'audit_logs';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'staff_id',
        'role',
        'action',
        'target_type',
        'target_id',
        'old_values',
        'new_values',
        'description',
        'ip_address',
        'user_agent',
    ];

    /**
     * The attributes that should be cast to native types.
     */
    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    /**
     * The staff member who performed the action.
     */
    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }

    /**
     * Scope: Filter by action type.
     */
    public function scopeAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope: Filter by target model type (e.g., Booking, Room).
     */
    public function scopeTargetType($query, string $type)
    {
        return $query->where('target_type', $type);
    }

    /**
     * Helper: Create an audit log entry.
     * Usage example:
     * AuditLog::record('approved_discount', $booking, $old, $new, 'Approved discount request');
     */
    public static function record(
        string $action,
        ?Model $target = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $description = null
    ): void {
        $staff = auth('staff')->user();

        self::create([
            'staff_id'   => $staff?->id,
            'role'       => $staff?->role ?? 'staff',
            'action'     => $action,
            'target_type'=> $target ? class_basename($target) : null,
            'target_id'  => $target?->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'description'=> $description,
            'ip_address' => request()->ip(),
            'user_agent' => request()->header('User-Agent'),
        ]);
    }
}