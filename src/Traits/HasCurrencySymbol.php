<?php

namespace QuickerFaster\UILibrary\Traits;

trait HasCurrencySymbol
{
    /**
     * Built-in currency code → symbol map.
     *
     * Consuming apps can extend this map by publishing a
     * config/payroll.php file with a 'currency_symbols' key,
     * or by defining a getCurrencySymbolOverrides() method
     * on the using class.
     */
    protected static array $builtInCurrencySymbols = [
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

    /**
     * Resolve a currency code to its display symbol.
     *
     * Resolution order:
     *  1. Instance method override (getCurrencySymbolOverrides)
     *  2. Config-based override (payroll.currency_symbols)
     *  3. Built-in map (this trait)
     *  4. Raw currency code as fallback
     */
    protected function getCurrencySymbol(string $currencyCode): string
    {
        return static::resolveCurrencySymbol($currencyCode, $this);
    }

    /**
     * Static resolver — usable from route closures and service classes
     * that cannot use the trait directly.
     *
     * @param string $currencyCode  e.g. "USD", "NGN"
     * @param object|null $context  Optional instance with getCurrencySymbolOverrides()
     */
    public static function resolveCurrencySymbol(string $currencyCode, ?object $context = null): string
    {
        $code = strtoupper($currencyCode);

        // 1. Instance-level overrides (method on the using class)
        if ($context !== null && method_exists($context, 'getCurrencySymbolOverrides')) {
            $overrides = $context->getCurrencySymbolOverrides();
            if (isset($overrides[$code])) {
                return $overrides[$code];
            }
        }

        // 2. Config-based overrides (consuming app can publish config/payroll.php)
        $configMap = config('payroll.currency_symbols', []);
        if (isset($configMap[$code])) {
            return $configMap[$code];
        }

        // 3. Built-in map
        if (isset(static::$builtInCurrencySymbols[$code])) {
            return static::$builtInCurrencySymbols[$code];
        }

        // 4. Fallback: return the code itself
        return $currencyCode;
    }
}