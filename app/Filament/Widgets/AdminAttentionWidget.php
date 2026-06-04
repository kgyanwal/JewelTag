<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class AdminAttentionWidget extends Widget
{
    protected static string $view = 'filament.widgets.admin-attention-widget';
    protected static ?int $sort = -10;
    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        $staff = \App\Helpers\Staff::user();
        return $staff?->hasAnyRole(['Superadmin', 'Administration']) ?? false;
    }

    public function getAlerts(): array
    {
        $alerts = [];

        // 1. Deleted sales
        try {
            $n = \App\Models\Sale::onlyTrashed()->count();
            if ($n > 0) $alerts[] = [
                'count'   => $n,
                'short'   => 'Deleted Sales',
                'message' => 'Deleted sales still have payments in EOD. Approve permanent deletion.',
                'bg'      => '#fef2f2',
                'border'  => '#ef4444',
                'iconBg'  => '#ef4444',
                'text'    => '#991b1b',
                'url'     => '/admin/archived-sales',
            ];
        } catch (\Exception $e) {}

        // 2. Archived stock
        try {
            $n = \App\Models\ProductItem::onlyTrashed()->count();
            if ($n > 0) $alerts[] = [
                'count'   => $n,
                'short'   => 'Archived Stock',
                'message' => 'Deleted stock items awaiting permanent removal or restoration.',
                'bg'      => '#fef2f2',
                'border'  => '#ef4444',
                'iconBg'  => '#ef4444',
                'text'    => '#991b1b',
                'url'     => '/admin/archived-stocks',
            ];
        } catch (\Exception $e) {}

        // 3. Pending refunds
        try {
            $n = \App\Models\Refund::where('status', 'pending')->count();
            if ($n > 0) $alerts[] = [
                'count'   => $n,
                'short'   => 'Pending Refunds',
                'message' => 'Customer refund requests awaiting approval.',
                'bg'      => '#fef2f2',
                'border'  => '#ef4444',
                'iconBg'  => '#ef4444',
                'text'    => '#991b1b',
                'url'     => '/admin/refunds',
            ];
        } catch (\Exception $e) {}

        // 4. Edit requests
        try {
            $n = \App\Models\SaleEditRequest::where('status', 'pending')->count();
            if ($n > 0) $alerts[] = [
                'count'   => $n,
                'short'   => 'Edit Requests',
                'message' => 'Staff waiting for edit approval on EOD locked sales.',
                'bg'      => '#fffbeb',
                'border'  => '#f59e0b',
                'iconBg'  => '#f59e0b',
                'text'    => '#92400e',
                'url'     => '/admin/sale-edit-requests',
            ];
        } catch (\Exception $e) {}

        // 5. Overdue laybuys
        try {
            $n = \App\Models\Laybuy::where('status', 'in_progress')
                ->whereNotNull('due_date')->where('due_date', '<', now())->count();
            if ($n > 0) $alerts[] = [
                'count'   => $n,
                'short'   => 'Overdue Laybuys',
                'message' => 'Layby plans past their payment deadline.',
                'bg'      => '#fffbeb',
                'border'  => '#f59e0b',
                'iconBg'  => '#f59e0b',
                'text'    => '#92400e',
                'url'     => '/admin/laybuys',
            ];
        } catch (\Exception $e) {}

        // 6. Overdue custom orders
        try {
            $n = \App\Models\CustomOrder::whereNotIn('status', ['completed', 'cancelled'])
                ->whereNotNull('due_date')->where('due_date', '<', now())->count();
            if ($n > 0) $alerts[] = [
                'count'   => $n,
                'short'   => 'Overdue Custom Orders',
                'message' => 'Custom orders past their promised delivery date.',
                'bg'      => '#fef2f2',
                'border'  => '#ef4444',
                'iconBg'  => '#ef4444',
                'text'    => '#991b1b',
                'url'     => '/admin/custom-orders',
            ];
        } catch (\Exception $e) {}

        // 7. Overdue repairs
        try {
            $n = \App\Models\Repair::whereNotIn('status', ['completed', 'cancelled', 'picked_up'])
                ->whereNotNull('customer_pickup_date')
                ->where('customer_pickup_date', '<', now()->toDateString())->count();
            if ($n > 0) $alerts[] = [
                'count'   => $n,
                'short'   => 'Overdue Repairs',
                'message' => 'Repairs past their promised customer pickup date.',
                'bg'      => '#fef2f2',
                'border'  => '#ef4444',
                'iconBg'  => '#ef4444',
                'text'    => '#991b1b',
                'url'     => '/admin/repairs',
            ];
        } catch (\Exception $e) {}

        // 8. Completed repairs not notified
        try {
            $n = \App\Models\Repair::where('status', 'completed')
                ->whereNull('notified_at')
                ->where('updated_at', '<', now()->subDays(3))->count();
            if ($n > 0) $alerts[] = [
                'count'   => $n,
                'short'   => 'Unnotified Repairs',
                'message' => 'Completed repairs — customer has not been notified.',
                'bg'      => '#fffbeb',
                'border'  => '#f59e0b',
                'iconBg'  => '#f59e0b',
                'text'    => '#92400e',
                'url'     => '/admin/repairs',
            ];
        } catch (\Exception $e) {}

        // 9. Stale pending sales
        try {
            $n = \App\Models\Sale::where('status', 'pending')
                ->where('balance_due', '>', 0)
                ->where('created_at', '<', now()->subDays(7))->count();
            if ($n > 0) $alerts[] = [
                'count'   => $n,
                'short'   => 'Unpaid Sales (7d+)',
                'message' => 'Pending sales with unpaid balances older than 7 days.',
                'bg'      => '#eff6ff',
                'border'  => '#3b82f6',
                'iconBg'  => '#3b82f6',
                'text'    => '#1e40af',
                'url'     => '/admin/sales',
            ];
        } catch (\Exception $e) {}

        // 10. Low stock
        try {
            $n = \App\Models\ProductItem::where('status', 'in_stock')
                ->where('qty', '<=', 2)->where('qty', '>', 0)->count();
            if ($n > 0) $alerts[] = [
                'count'   => $n,
                'short'   => 'Low Stock',
                'message' => 'Items with qty ≤ 2 that may need restocking.',
                'bg'      => '#eff6ff',
                'border'  => '#3b82f6',
                'iconBg'  => '#3b82f6',
                'text'    => '#1e40af',
                'url'     => '/admin/product-items',
            ];
        } catch (\Exception $e) {}

        return $alerts;
    }
}