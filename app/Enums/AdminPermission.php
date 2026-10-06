<?php

namespace App\Enums;

enum AdminPermission: string
{
    case DashboardView = 'dashboard.view';
    case OrdersManage = 'orders.manage';
    case PaymentsManage = 'payments.manage';
    case CatalogManage = 'catalog.manage';
    case InventoryManage = 'inventory.manage';
    case CustomersManage = 'customers.manage';
    case DeliveryManage = 'delivery.manage';
    case ReturnsManage = 'returns.manage';
    case ReviewsManage = 'reviews.manage';
    case MarketingManage = 'marketing.manage';
    case ReportsView = 'reports.view';
    case ActivityView = 'activity.view';
    case SettingsManage = 'settings.manage';
    case HomepageManage = 'homepage.manage';
    case WishlistView = 'wishlist.view';
    case StaffManage = 'staff.manage';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $permission): string => $permission->value, self::cases());
    }
}
