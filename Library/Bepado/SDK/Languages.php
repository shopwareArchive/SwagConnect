<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Bepado\SDK;

/**
 * Language codes confirming to ISO 639-1:2002.
 *
 * @see https://en.wikipedia.org/wiki/ISO_639-1
 */
class Languages
{
    /**
     * @var string[]
     */
    private static $languageCodes = array(
        'aa', 'ab', 'af', 'am', 'ar', 'as', 'ay', 'az',
        'ba', 'be', 'bg', 'bh', 'bi', 'bn', 'bo', 'br',
        'ca', 'co', 'cs', 'cy', 'da', 'de', 'dz', 'el',
        'en', 'eo', 'es', 'et', 'eu', 'fa', 'fi', 'fj',
        'fo', 'fr', 'fy', 'ga', 'gd', 'gl', 'gn', 'gu',
        'ha', 'hi', 'he', 'hr', 'hu', 'hy', 'ia', 'id',
        'ie', 'ik', 'in', 'is', 'it', 'iu', 'iw', 'ja',
        'ji', 'jw', 'ka', 'kk', 'kl', 'km', 'kn', 'ko',
        'ks', 'ku', 'ky', 'la', 'ln', 'lo', 'lt', 'lv',
        'mg', 'mi', 'mk', 'ml', 'mn', 'mo', 'mr', 'ms',
        'mt', 'my', 'na', 'ne', 'nl', 'no', 'oc', 'om',
        'or', 'pa', 'pl', 'ps', 'pt', 'qu', 'rm', 'rn',
        'ro', 'ru', 'rw', 'sa', 'sd', 'sg', 'sh', 'si',
        'sk', 'sl', 'sm', 'sn', 'so', 'sq', 'sr', 'ss',
        'st', 'su', 'sv', 'sw', 'ta', 'te', 'tg', 'th',
        'ti', 'tk', 'tl', 'tn', 'to', 'tr', 'ts', 'tt',
        'tw', 'ug', 'uk', 'ur', 'uz', 'vi', 'vo', 'wo',
        'xh', 'yi', 'yo', 'za', 'zh', 'zu',
    );

    /**
     * Checks for a valid ISO 639-1 language code (lower case).
     *
     * @param string $potentialLanguageCode
     * @return bool
     */
    public static function isValidLanguageCode($potentialLanguageCode)
    {
        return (array_search($potentialLanguageCode, self::$languageCodes) !== false);
    }
}
