<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Bepado\SDK\Struct;

use Bepado\SDK\Struct;

/**
 * Struct class, representing products
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 * @api
 */
class Product extends ShopItem
{
    /**
     * Describes the weight of this product, e.g. "4.8" in kilograms Kg
     *
     * May be used for shipping cost weight calculation.
     */
    const ATTRIBUTE_WEIGHT = 'weight';

    /**
     * Describes the volume of this product, e.g. "0.75" in liters (L)
     */
    const ATTRIBUTE_VOLUME = 'volume';

    /**
     * Describes the product dimension, e.g. 40x20x100 (Length, Width, Height)
     */
    const ATTRIBUTE_DIMENSION = 'dimension';

    /**
     * Describes the unit of this product, for example "kg" or "ml".
     *
     * Has to be a unit from the available units defined in
     * {@see \Bepado\SDK\Units::$units}.
     *
     * To check if a unit is avialable call {Bepado\SDK\Units::exists($unit)}.
     */
    const ATTRIBUTE_UNIT = 'unit';

    /**
     * Reference quantity in the configured unit
     */
    const ATTRIBUTE_REFERENCE_QUANTITY = 'ref_quantity';

    /**
     * Units of the reference quantity the product has
     */
    const ATTRIBUTE_QUANTITY = 'quantity';

    /**
     * Decribes the size of a variant.
     */
    const VARIANT_SIZE = 'size';

    /**
     * Describes the color of a variant.
     */
    const VARIANT_COLOR = 'color';

    /**
     * Describes the material of a variant.
     */
    const VARIANT_MATERIAL = 'material';

    /**
     * Decribes the pattern of a variant.
     */
    const VARIANT_PATTERN = 'pattern';

    /**
     * Local ID of the product in your shop.
     *
     * ID should never change for one product or be reused for another product.
     *
     * @var string
     */
    public $sourceId;

    /**
     * Shared ID for product variants.
     *
     * This ID must be identical for all variants of the same product. If the product
     * has no variants this property can be NULL.
     *
     * @var string
     */
    public $groupId;

    /**
     * The European Article Number (EAN) of the product.
     *
     * @var string
     */
    public $ean;

    /**
     * URL to the product in your shop.
     *
     * Used for redirects to the product, or views of the product.
     *
     * @var string
     */
    public $url;

    /**
     * Title / name of the product
     *
     * @var string
     */
    public $title;

    /**
     * A short description of the product
     *
     * May contain simple HTML
     *
     * @var string
     */
    public $shortDescription;

    /**
     * An extensive / full description of the product
     *
     * May contain simple HTML
     *
     * @var string
     */
    public $longDescription;

    /**
     * Name of the product vendor
     *
     * @var string
     */
    public $vendor;

    /**
     * The language of the product title, description etc.
     *
     * @var string
     */
    public $language = 'de';

    /**
     * The value added tax for this product. The property must be set as a numerical
     * value between 0 and 1. Default value is 0.19, the German default VAT.
     *
     * @var float
     */
    public $vat = 0.19;

    /**
     * Current price of the product.
     *
     * Provided as a float. This is the selling price of the product
     * that end customers will pay. The price is checked again
     * during transactions and is required to be the same in both
     * shops during a transaction.
     *
     * The price is net and does *NOT* include the VAT.
     *
     * @var float
     */
    public $price;

    /**
     * The purchase price of this product.
     *
     * This is the price the seller (from-shop) of a product offers
     * a reseller (to-shop) for transactions in Bepado.
     * The price - purchasePrice gap is the profit of the reseller.
     *
     * Defining this price is optional and the regular Bepado price groups
     * will take effect if its not given. If this price is given however
     * price groups will NOT be considered to calculate the profit margin.
     *
     * The price is net and does *NOT* include the VAT.
     *
     * @var float
     */
    public $purchasePrice;

    /**
     * Do national laws require the price to be fixed at the suppliers level?
     *
     * This flag covers laws such as "Buchpreisbindung" in Germany.
     * SDK implementors have to force the selling price to customers to
     * be the same as given by the suplier.
     *
     * If this flag is not set, then selling shops are free to change
     * the price in their shops to their wishes and SDK implementors
     * **HAVE** to grant Shop users this possibility.
     *
     * This flag is **ONLY** for national price laws, not to prevent your
     * partners to change the price. Using this flag for preventing partners
     * to change the price is not allowed.
     *
     * @var boolean
     */
    public $fixedPrice = false;

    /**
     * Currency of the price
     *
     * Currently only the default "EUR" is supported.
     *
     * @var string
     */
    public $currency = "EUR";

    /**
     * Override for shipping costs based on article.
     *
     * @var string
     */
    public $shipping;

    /**
     * Optional delivery date for this product as a unix timestamp.
     *
     * @var integer
     */
    public $deliveryDate;

    /**
     * Availability of the product
     *
     * Provide an integer with the amount of products currently in stock and
     * ready for delivery. When comparing availability during a transaction
     * Bepado SDK will group the availability into empty, low, medium and high
     * groups based on the interval 0 < 1-10 (low) < 11-100 (medium) < 101 to
     * infinity (high).
     *
     * @var integer
     */
    public $availability;

    /**
     * List of product image URLs
     *
     * The order of the images is relevant. The main image of the product
     * has to be at position 0 and is the only one used for presenting
     * the product on window-shopping and the product finder inside bepado.
     *
     * @var string[]
     */
    public $images = array();

    /**
     * Product categories.
     *
     * For a full list of currently available product categories call
     * getCategories() on the SDK class.
     *
     * @var string[]
     */
    public $categories = array();

    /**
     * Product Tags
     *
     * A list of tags that can help other shops find your product.
     * Is limited to 10 tags maximum per product.
     *
     * @var array
     */
    public $tags = array();

    /**
     * Factor that affects the boost of products in search results.
     * Valid values are -1, 0, 1.
     *
     * @var int
     */
    public $relevance = 0;

    /**
     * Contains additional attributes for this product. Use one of the constants
     * defined in this class to specify a an attribute:
     *
     * <code>
     * $product->attributes[Product::ATTRIBUTE_UNIT] = 'kg';
     * $product->attributes[Product::ATTRIBUTE_WEIGHT] = '1.0';
     * $product->attributes[Product::ATTRIBUTE_DIMENSION] = '20x30x40';
     * </code>
     *
     * @var string[]
     */
    public $attributes = array();

    /**
     * Variant attributes, represented as key value pairs with data.
     *
     * There is a list of predefiend variant keys for common values:
     *
     * - "size"
     * - "color"
     * - "material"
     * - "pattern"
     *
     * @var array
     */
    public $variant = array();

    /**
     * Workdays until this product can be delivered.
     *
     * @var int
     */
    public $deliveryWorkDays;

    /**
     * Translations, indexed by ISO 639-1 language code (e.g. "de").
     *
     * @see https://en.wikipedia.org/wiki/ISO_639-1
     *
     * @var \Bepado\SDK\Struct\Translation[string]
     */
    public $translations = array();

    /**
     * Restores a product from a previously stored state array.
     *
     * @param array $state
     * @return \Bepado\SDK\Struct\Product
     */
    public static function __set_state(array $state)
    {
        return new Product($state);
    }

    public function &__get($property)
    {
        switch ($property) {
            case 'freeDelivery':
                $val = false; // return by reference hack
                return $val;

            default:
                return parent::__get($property);
        }
    }

    public function __set($property, $value)
    {
        switch ($property) {
            case 'freeDelivery':
                // Ignored as of newest version, use $shipping instead
                break;

            default:
                return parent::__set($property, $value);
        }
    }

    /**
     * Return the variant data as string representation.
     *
     * It combines all key-value pairs using a ; sign.
     *
     * @return string
     */
    public function getVariantString()
    {
        $data = array();

        foreach ($this->variant as $key => $value) {
            $data[] = $key . '=' . $value;
        }

        return implode(';', $data);
    }
}
