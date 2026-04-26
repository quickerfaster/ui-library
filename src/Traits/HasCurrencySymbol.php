<?php

namespace QuickerFaster\UILibrary\Traits;

trait HasCurrencySymbol
{
    protected function getCurrencySymbol(string $currencyCode): string
    {
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'JPY' => '¥',
            'CAD' => 'C$',
            'AUD' => 'A$',
            'INR' => '₹',
            'NGN' => '₦',
            'CNY' => '¥',
            'CHF' => 'Fr',
            'SEK' => 'kr',
            'NOK' => 'kr',
            'DKK' => 'kr',
            'MXN' => '$',
            'BRL' => 'R$',
            'ZAR' => 'R',
        ];
        return $symbols[strtoupper($currencyCode)] ?? $currencyCode;
    }
}