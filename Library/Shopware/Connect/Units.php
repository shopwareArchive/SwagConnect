<?php
/**
 * This file is part of the Shopware Connect SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Shopware\Connect;

/**
 * Units and their symbols. Used for product details
 */
class Units
{
    /**
     * @var array
     */
    private static $units = array(
        'b' => array(
            'en' => 'Byte(s)',
            'de' => 'Byte',
        ),
        'kb' => array(
            'en' => 'Kilobyte(s)',
            'de' => 'Kilobyte',
        ),
        'mb' => array(
            'en' => 'Megabyte(s)',
            'de' => 'Megabyte',
        ),
        'gb' => array(
            'en' => 'Gigabyte(s)',
            'de' => 'Gigabyte',
        ),
        'tb' => array(
            'en' => 'Terabyte(s)',
            'de' => 'Terabyte',
        ),
        'g' => array(
            'en' => 'Gram(s)',
            'de' => 'Gramm',
        ),
        'kg' => array(
            'en' => 'Kilogram(s)',
            'de' => 'Kilogramm',
        ),
        'mg' => array(
            'en' => 'Milligram(s)',
            'de' => 'Milligramm',
        ),
        'oz' => array(
            'en' => 'Ounce(s)',
            'de' => 'Unze',
        ),
        'lb' => array(
            'en' => 'Pound(s)',
            'de' => 'Pfund',
        ),
        't' => array(
            'en' => 'Ton(s)',
            'de' => 'Tonne',
        ),
        'l' => array(
            'en' => 'Litre(s)',
            'de' => 'Liter',
        ),
        'ft^3' => array(
            'en' => 'Cubic foot/feet',
            'de' => 'Kubikfuß',
        ),
        'in^3' => array(
            'en' => 'Cubic inch(es)',
            'de' => 'Kubikzoll',
        ),
        'm^3' => array(
            'en' => 'cubic meter',
            'de' => 'Kubikmeter',
        ),
        'yd^3' => array(
            'en' => 'cubic yard(s)',
            'de' => 'Kubikyard',
        ),
        'fl oz' => array(
            'en' => 'fluid ounce(s)',
            'de' => 'Flüssigunze',
        ),
        'gal' => array(
            'en' => 'Gallon(s)',
            'de' => 'Gallonen',
        ),
        'ml' => array(
            'en' => 'Millilitre(s)',
            'de' => 'Milliliter',
        ),
        'qt' => array(
            'en' => 'Quart(s)',
            'de' => 'Quart',
        ),
        'm' => array(
            'en' => 'Metre(s)',
            'de' => 'Meter',
        ),
        'cm' => array(
            'en' => 'Centimetre(s)',
            'de' => 'Zentimeter',
        ),
        'ft' => array(
            'en' => 'Foot/feet',
            'de' => 'Fuß',
        ),
        'in' => array(
            'en' => 'Inch(es)',
            'de' => 'Zoll',
        ),
        'km' => array(
            'en' => 'Kilometre(s)',
            'de' => 'Kilometer',
        ),
        'mm' => array(
            'en' => 'Millimetre(s)',
            'de' => 'Millimeter',
        ),
        'yd' => array(
            'en' => 'yard(s)',
            'de' => 'Yard',
        ),
        'piece' => array(
            'en' => 'Piece(s)',
            'de' => 'Stück',
        ),
        'bottle' => array(
            'en' => 'Bottle(s)',
            'de' => 'Flasche',
        ),
        'crate' => array(
            'en' => 'Crate(s)',
            'de' => 'Kiste',
        ),
        'can' => array(
            'en' => 'Can(s)',
            'de' => 'Dose',
        ),
        'capsule' => array(
            'en' => 'Capsule(s)',
            'de' => 'Kapsel',
        ),
        'box' => array(
            'en' => 'Box(es)',
            'de' => 'Karton(s)',
        ),
        'glass' => array(
            'en' => 'Glass(es)',
            'de' => 'Glas',
        ),
        'kit' => array(
            'en' => 'Kit(s)',
        ),
        'pack' => array(
            'en' => 'Pack(s)',
            'de' => 'Packung(en)',
        ),
        'package' => array(
            'en' => 'Package(s)',
            'de' => 'Paket(e)',
        ),
        'pair' => array(
            'en' => 'Pair(s)',
            'de' => 'Paar',
        ),
        'roll' => array(
            'en' => 'Roll(s)',
            'de' => 'Rolle',
        ),
        'set' => array(
            'en' => 'Set(s)',
        ),
        'sheet' => array(
            'en' => 'Sheet(s)',
            'de' => 'Blatt',
        ),
        'ticket' => array(
            'en' => 'Ticket(s)',
        ),
        'unit' => array(
            'en' => 'Unit(s)',
            'de' => 'VKE',
        ),
        'second' => array(
            'en' => 'Second(s)',
            'de' => 'Sekunde',
        ),
        'day' => array(
            'en' => 'Day(s)',
            'de' => 'Tag',
        ),
        'hour' => array(
            'en' => 'Hour(s)',
            'de' => 'Stunde',
        ),
        'minute' => array(
            'en' => 'Minute(s)',
            'de' => 'Minute',
        ),
        'month' => array(
            'en' => 'Month(s)',
            'de' => 'Monat(e)',
        ),
        'night' => array(
            'en' => 'Night(s)',
            'de' => 'Nacht',
        ),
        'week' => array(
            'en' => 'Week(s)',
            'de' => 'Woche',
        ),
        'year' => array(
            'en' => 'Year(s)',
            'de' => 'Jahr(e)',
        ),
        'm^2' => array(
            'en' => 'Square metre(s)',
            'de' => 'Quadratmeter',
        ),
        'cm^2' => array(
            'en' => 'Square centimetre(s)',
            'de' => 'Quadratzentimeter',
        ),
        'ft^2' => array(
            'en' => 'Square foot/feet',
            'de' => 'Quadratfuß',
        ),
        'in^2' => array(
            'en' => 'Square inch(es)',
            'de' => 'Quadratzoll',
        ),
        'mm^2' => array(
            'en' => 'Square milimetre(s)',
            'de' => 'Quadratmillimeter',
        ),
        'yd^2' => array(
            'en' => 'Square yard(s)',
            'de' => 'Quadratyard',
        ),
        'lfm' => array(
            'en' => 'Running metre',
            'de' => 'Laufender Meter'
        )
    );

    /**
     * List of all available unit symbols.
     */
    public static function getAvailableUnits()
    {
        return array_keys(self::$units);
    }

    /**
     * Key value pairs of unit symbols and their translated unit name.
     *
     * @return array
     */
    public static function getLocalizedUnits($locale = 'en')
    {
        return array_map(function ($labels) use ($locale) {
                return isset($labels[$locale]) ? $labels[$locale] : $labels['en'];
            },
            self::$units
        );
    }

    /**
     * Does this unit exist as a measurement in Shopware Connect
     *
     * @return bool
     */
    public static function exists($unitSymbol)
    {
        return isset(self::$units[strtolower($unitSymbol)]);
    }
}
