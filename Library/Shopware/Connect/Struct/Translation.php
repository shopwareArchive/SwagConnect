<?php
/**
 * This file is part of the Shopware Connect SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Shopware\Connect\Struct;

use Shopware\Connect\Struct;

/**
 * Struct class, representing translation information for a product
 *
 * Translations are stored in the product struct and mapped to their
 * corresponding language code there. If a translation does not provide a
 * certain field, a fall back to the default product data will occur (e.g. if
 * Translation#title is left null, Product#title should be used for that language).
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 * @api
 */
class Translation extends Struct
{
    /**
     * Title / name of the product in the specific language
     *
     * @var string
     */
    public $title;

    /**
     * A short description of the product in the specific language
     *
     * @var string
     */
    public $shortDescription;

    /**
     * An extensive / full description of the product in the specific language
     *
     * @var string
     */
    public $longDescription;

    /**
     * An additional description of the product in the specific language
     *
     * @var string
     */
    public $additionalDescription;

    /**
     * Translations for the keys used in Product::$variant.
     *
     * Map variant keys to a language specific label. For example "size" =>
     * "Größe".
     *
     * @var string[string]
     */
    public $variantLabels = array();

    /**
     * Translations for the values used in Product::$variant.
     *
     * Map variant values to their translations. For example "red" => "Rot".;
     *
     * @var string[string]
     */
    public $variantValues = array();

    /**
     * Link to the detail page of the product in this language.
     *
     * @var string
     */
    public $url;
}
