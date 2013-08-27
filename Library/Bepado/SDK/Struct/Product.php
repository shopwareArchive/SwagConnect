<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version $Revision$
 */

namespace Bepado\SDK\Struct;

use Bepado\SDK\Struct;

/**
 * Struct class, representing products
 *
 * @version $Revision$
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
     * Provided as a float. This is the selling price of the product
     * that end customers will pay. The price is checked again
     * during transactions and is required to be the same in both
     * shops during a transaction.
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
