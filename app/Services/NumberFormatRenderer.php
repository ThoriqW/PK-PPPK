<?php

namespace App\Services;

/**
 * Renders an agreement number from a configurable format string.
 * Supported placeholders:
 *   {seq}          zero-padded counter
 *   {year}         4-digit year
 *   {month}        2-digit month
 *   {roman_month}  I, II, III, ... XII
 *   {opd}          OPD code
 */
class NumberFormatRenderer
{
    /**
     * @param array{seq:int, padding:int, year:int, month:int, opd?:string|null} $context
     */
    public function render(string $format, array $context): string
    {
        $seq = str_pad((string) $context['seq'], (int) ($context['padding'] ?? 1), '0', STR_PAD_LEFT);

        $replacements = [
            '{seq}'         => $seq,
            '{year}'        => str_pad((string) $context['year'], 4, '0', STR_PAD_LEFT),
            '{month}'       => str_pad((string) $context['month'], 2, '0', STR_PAD_LEFT),
            '{roman_month}' => $this->toRoman((int) $context['month']),
            '{opd}'         => (string) ($context['opd'] ?? ''),
        ];

        return strtr($format, $replacements);
    }

    private function toRoman(int $month): string
    {
        $map = ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'];
        return $map[$month - 1] ?? (string) $month;
    }
}
