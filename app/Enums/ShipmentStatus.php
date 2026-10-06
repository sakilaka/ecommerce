<?php

namespace App\Enums;

enum ShipmentStatus: string
{
    case Pending = 'pending';
    case ReadyToShip = 'ready_to_ship';
    case Shipped = 'shipped';
    case InTransit = 'in_transit';
    case Delivered = 'delivered';
    case Failed = 'failed';
    case Returned = 'returned';
    case Cancelled = 'cancelled';
}
