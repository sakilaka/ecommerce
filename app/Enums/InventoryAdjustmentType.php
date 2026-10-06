<?php

namespace App\Enums;

enum InventoryAdjustmentType: string
{
    case Manual = 'manual';
    case Purchase = 'purchase';
    case Sale = 'sale';
    case Return = 'return';
    case Correction = 'correction';
}
