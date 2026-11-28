<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Draft     = 'draft';
    case Confirmed = 'confirmed';
    case Shipped   = 'shipped';
    case Cancelled = 'cancelled';

    public function canBeShipped(): bool
    {
        return $this === self::Confirmed;
    }

    public function canBeCancelled(): bool
    {
        return in_array($this, [
            self::Draft,
            self::Confirmed,
        ], true);
    }
}
