<?php

namespace QuickerFaster\UILibrary\Services\BankFiles;

class BankFileGeneratorFactory
{
    protected static array $sepaCountries = [
        'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR', 'DE', 'GR',
        'HU', 'IS', 'IE', 'IT', 'LV', 'LI', 'LT', 'LU', 'MT', 'NL', 'NO', 'PL',
        'PT', 'RO', 'SK', 'SI', 'ES', 'SE', 'CH', 'GB'
    ];

    public static function make(string $countryCode): BankFileGenerator
    {
        if (in_array($countryCode, self::$sepaCountries)) {
            return new SEPAGenerator();
        }

        return match ($countryCode) {
            'US' => new NACHAGenerator(),
            'UK' => new BACSGenerator(),
            'NG' => new NIBSSGenerator(),
            default => throw new \Exception("No bank file generator for country: {$countryCode}"),
        };
    }
}