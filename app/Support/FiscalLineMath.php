<?php

namespace App\Support;

final class FiscalLineMath
{
    /**
     * @return array{line_subtotal: string, line_tax: string, line_total: string}
     */
    public static function lineTotals(string $qty, string $unitPrice, string $taxRatePercent): array
    {
        $subtotal = bcmul($qty, $unitPrice, 4);
        $tax = bcmul($subtotal, bcdiv($taxRatePercent, '100', 10), 4);
        $total = bcadd($subtotal, $tax, 4);

        return [
            'line_subtotal' => $subtotal,
            'line_tax' => $tax,
            'line_total' => $total,
        ];
    }
}
