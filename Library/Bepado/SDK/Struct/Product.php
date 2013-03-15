<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version 1.0.0snapshot201303151129
 */

namespace Bepado\SDK\Struct;

use Bepado\SDK\Struct;

/**
 * Struct class, representing products
 *
 * @version 1.0.0snapshot201303151129
 * @api
 */
class Product extends ShopItem
{
    /**
     * Describes the unit of this product, for example "Kg" or "ml"
     */
    const ATTRIBUTE_UNIT = 'unit';

    /**
     * Describes the weight of this product, e.g. "4.8" Kg
     */
    const ATTRIBUTE_WEIGHT = 'weight';

    /**
     * Describes the volume of this product, e.g. "0.75" L
     */
    const ATTRIBUTE_VOLUME = 'volume';

    /**
     * Describes the product dimension, e.g. 40 x 20 x 100
     */
    const ATTRIBUTE_DIMENSION = 'dimension';

    /**
     * Describes the base weight of a product, e.g "1.0" Kg for a product with
     * a weight of 0.500 Kg
     */
    const ATTRIBUTE_BASE_WEIGHT = 'base_weight';

    /**
     * Describes the base volume of a product, e.g "1.0" L for a product with
     * a weight of 0.75 L
     */
    const ATTRIBUTE_BASE_VOLUME = 'base_volume';

    /**
     * Local ID of the product in your shop.
     *
     * ID should never change for one product or be reused for another product.
     *
     * @var string
     */
    public $sourceId;

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
     * The value added tax for this product. The property must be set as a float
     * value. At the moment only 0.00, 0.07 and 0.19 are supported.
     *
     * @var float
     */
    public $vat = 0.19;

    /**
     * Current price of the product.
     *
     * Provided as a float.
     *
     * @var float
     */
    public $price;

    /**
     * The purchase price of this product.
     *
     * @var float
     */
    public $purchasePrice;

    /**
     * Currency of the price
     *
     * Currently only the default "EUR" is supported.
     *
     * @var string
     */
    public $currency = "EUR";

    /**
     * If this property is set to <b>TRUE</b> this product will be shown as
     * delivery free of charge.
     *
     * @var boolean
     */
    public $freeDelivery = false;

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
     * ready for delivery.
     *
     * @var integer
     */
    public $availability;

    /**
     * List of product image URLs
     *
     * @var string[][]
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
     * Restores a product from a previously stored state array.
     *
     * @param array $state
     * @return \Bepado\SDK\Struct\Product
     */
    public static function __set_state(array $state)
    {
        return new Product($state);
    }
}
