<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Bepado\SDK;

/**
 * Countries and their ISO2 and ISO3 codes.
 */
class Countries
{
    /**
     * @var array
     */
    private static $countries = array(
        'DE' => 'DEU',
        'FR' => 'FRA',
        'CH' => 'CHE',
        'AT' => 'AUT',
        'BE' => 'BEL',
        'BGR' => 'BGR',
        'DK' => 'DNK',
        'EE' => 'EST',
        'FI' => 'FIN',
        'GR' => 'GRC',
        'IE' => 'IRL',
        'IT' => 'ITA',
        'HR' => 'HRV',
        'LV' => 'LVA',
        'LT' => 'LTU',
        'LU' => 'LUX',
        'MT' => 'MLT',
        'NL' => 'NLD',
        'PL' => 'POL',
        'PT' => 'PRT',
        'RO' => 'ROM',
        'SE' => 'SWE',
        'SK' => 'SVK',
        'SI' => 'SVN',
        'ES' => 'ESP',
        'CZ' => 'CZE',
        'HU' => 'HUN',
        'GB' => 'GBR',
        'CY' => 'CYP',
        'EU' => 'EUR',
    );

    /**
     * List of all available countries.
     */
    public static function getAvailableUnits()
    {
        return self::$countries;
    }


    /**
     * Returns ISO2 code by given ISO3
     *
     * @param string $iso3
     * @return string
     * @throws \Exception
     */
    public static function getISO2($iso3)
    {
        $iso2 = array_search($iso3, self::$countries);
        if ($iso2 === false) {
            throw new \InvalidArgumentException('Country not found!');
        }

        return $iso2;
    }

    /**
     * Returns ISO3 code by given ISO2
     * @param $iso2
     * @return mixed
     * @throws \Exception
     */
    public static function getISO3($iso2)
    {
        if (!isset(self::$countries[$iso2])) {
            throw new \InvalidArgumentException('Country not found!');
        }

        return self::$countries[$iso2];
    }

    /**
     * Checks for existing country
     * by ISO2 or ISO3 code
     *
     * @param string $countryCode
     * @return bool
     */
    public static function exists($countryCode)
    {
        if (isset(self::$countries[$countryCode])) {
            return true;
        }

        if (array_search($countryCode, self::$countries)) {
            return true;
        }

        return false;
    }
} 