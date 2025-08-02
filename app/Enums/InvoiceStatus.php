<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case DRAFT      = 'draft';
    case SENT       = 'sent';
    case PARTIAL    = 'partial';
    case PAID       = 'paid';
    case CANCELLED  = 'cancelled';
    case PENDING    = 'pending';
    case PAYMENTONREVIEW    = 'payment on review';
}

