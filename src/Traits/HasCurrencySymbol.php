<?php

namespace QuickerFaster\UILibrary\Traits;

use QuickerFaster\UILibrary\Contracts\Navigation\CompanyProvider;

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
     * Resolve the current company's currency code using the library's
     * CompanyProvider contract and session context.
     *
     * Resolution order:
     *  1. Session 'current_company_id' (set by ResolveCompanyContext middleware)
     *  2. CompanyProvider::getCurrentCompanyId()
     *  3. Company object's currency_code attribute (if available)
     *  4. null (caller falls back to 'USD')
     *
     * This keeps the library independent of App\Modules\* — the consuming
     * app binds a CompanyProvider implementation that returns company objects.
     */
    protected function getCurrentCompanyCurrencyCode(): ?string
    {
        $sessionKey = (string) config('ui-library.tenancy.session_key', 'current_company_id');
        $companyId = session($sessionKey);

        // "All Companies" (0) has no single currency
        if ($companyId === 0) {
            return null;
        }

        try {
            $provider = app(CompanyProvider::class);

            if ($companyId === null) {
                $companyId = $provider->getCurrentCompanyId(auth()->user());
            }

            if (!$companyId) {
                return null;
            }

            $company = $provider->getCompanies(auth()->user())
                ->firstWhere('id', $companyId);

            if ($company && isset($company->currency_code) && $company->currency_code) {
                return (string) $company->currency_code;
            }
        } catch (\Throwable $e) {
            // CompanyProvider may not be bound or may throw — fall through
        }

        return null;
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