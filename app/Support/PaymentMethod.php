<?php

namespace App\Support;

/**
 * Canonical payment methods used across sales, installments, and supplier payments.
 * Change values here (and DB enums if any) when adding new methods.
 */
final class PaymentMethod
{
    public const CASH = 'cash';

    public const GCASH = 'gcash';

    public const BANK_TRANSFER = 'bank_transfer';

    public const CHEQUE = 'cheque';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return [
            self::CASH,
            self::GCASH,
            self::BANK_TRANSFER,
            self::CHEQUE,
        ];
    }

    public static function rule(): string
    {
        return 'in:' . implode(',', self::values());
    }
}
