<?php

namespace App\Enums;

/** An order is pending while its payment runs, then paid or failed. */
enum OrderStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
}
