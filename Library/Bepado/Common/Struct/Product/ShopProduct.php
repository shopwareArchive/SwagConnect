<?php
/**
 * This file is part of the Bepado Common Component.
 *
 * @version $Revision$
 */

namespace Bepado\Common\Struct\Product;

use Bepado\Common\Struct\Product;

/**
 *
 */
class ShopProduct extends Product
{
    /**
     * @var string
     */
    public $shopId;

    /**
     * @var string
     */
    public $sourceId;

    /**
     * @var integer
     */
    public $masterId;

    /**
     * @var bool
     */
    public $approved = false;

    /**
     * The purchase price of this product.
     *
     * @var float
     */
    public $purchasePrice;

    /**
     * National Law fixes prices:
     *
     * Example: Deutsche Buchpreisbindung
     *
     * @var bool
     */
    public $fixedPrice;

    /**
     * The gross margin for sellers in percent of the purchase price.
     *
     * @link http://de.wikipedia.org/wiki/Handelsspanne
     * @var float
     */
    public $grossMarginPercent;

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
     * Factor that affects the boost of products in search results.
     * Valid values are -1, 0, 1.
     *
     * @var int
     */
    public $relevance = 0;
}
