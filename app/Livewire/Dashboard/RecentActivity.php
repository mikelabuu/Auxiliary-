<?php

namespace App\Livewire\Dashboard;

use Livewire\Component;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * The dashboard's Recent Activity feed. Was a controller variable frozen at
 * page load; as a component it polls and follows the same broadcast pushes
 * as the other dashboard cards, so new check-ins/cancellations/room changes
 * appear within seconds.
 */
class RecentActivity extends Component
{
    protected $listeners = ['refreshRecentActivity' => '$refresh'];

    public function render()
    {
        $recentActivities = DB::table('audit_logs')
            ->leftJoin('staff', 'audit_logs.staff_id', '=', 'staff.id')
            ->select('audit_logs.*', 'staff.name as staff_name')
            ->orderBy('audit_logs.created_at', 'desc')
            ->limit(4)
            ->get()
            ->map(function ($log) {
                // Names index app/Support/AdminIcons (Font Awesome Free), which
                // is what the <x-admin.ui.icon> in the view resolves against.
                $icon = 'check-circle';
                $colorClass = 'bg-clsu-50 text-clsu-700';

                $action = strtolower($log->action);
                $target = strtolower($log->target_type);

                if (str_contains($action, 'booking') || $target === 'booking' || $target === 'reservation') {
                    $icon = 'receipt';
                    $colorClass = 'bg-clsu-50 text-clsu-700';
                } elseif (str_contains($action, 'user') || str_contains($action, 'staff') || $target === 'user' || $target === 'staff') {
                    $icon = 'user';
                    $colorClass = 'bg-palay-50 text-palay-700';
                } elseif (str_contains($action, 'room') || $target === 'room') {
                    $icon = 'bed';
                    $colorClass = 'bg-stone-100 text-muted';
                }

                return [
                    'description' => $log->description ?: "Action '{$log->action}' executed",
                    'created_at' => Carbon::parse($log->created_at)->diffForHumans(),
                    'icon' => $icon,
                    'color_class' => $colorClass,
                ];
            });

        // Fallback for demo/empty state
        if ($recentActivities->isEmpty()) {
            $recentActivities = collect([
                [
                    'description' => 'System initialized · 22 rooms added',
                    'created_at' => '1 week ago',
                    'icon' => 'settings',
                    'color_class' => 'bg-stone-100 text-muted',
                ]
            ]);
        }

        return view('livewire.dashboard.recent-activity', [
            'recentActivities' => $recentActivities,
        ]);
    }
}
