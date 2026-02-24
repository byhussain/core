<?php

namespace SmartTill\Core\Enums;

enum CashTransactionType: string
{
    case SalePaid = 'sale_paid';
    case PaymentReceived = 'payment_received';
    case SaleRefunded = 'sale_refunded';
    case SaleCancelled = 'sale_cancelled';
    case CashCollected = 'cash_collected';
}
