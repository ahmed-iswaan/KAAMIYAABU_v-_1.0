<?php

namespace App\Enums;

enum InvoiceType: string
{
    case STANDARD    = 'standard';
    case PROFORMA    = 'proforma';
    case CREDIT_MEMO = 'credit_memo';
    case RECURRING   = 'recurring';
    case DEPOSIT     = 'deposit';
}
