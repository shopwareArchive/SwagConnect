<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version $Revision$
 */

namespace Bepado\SDK\Struct\SearchResult;

use Bepado\SDK\Struct;

/**
 * Struct class representing a product in a search result
 *
 * @version $Revision$
 * @api
 */
class Product extends Struct
{
    /**
     * Product title
     *
     * @var string
     */
    public $title;

    /**
     * Product short description
     *
     * @var string
     */
    public $shortDescription;

    /**
     * Product full description
     *
     * @var string
     */
    public $longDescription;

    /**
     * Product categories
     *
     * @var string[]
     */
    public $categories;

    /**
     * Lowest product price
     *
     * @var float
     */
    public $priceFrom;

    /**
     * Highest product price
     *
     * @var float
     */
    public $priceTo;

    /**
     * Product price currency
     *
     * @var string
     */
    public $currency;

    /**
     * Product availability
     *
     * @var int
     */
    public $availability;

    /**
     * Product language
     *
     * @var string
     */
    public $language;

    /**
     * Product vendor name
     *
     * @var string
     */
    public $vendor;

    /**
     * Product URL
     *
     * @var string
     */
    public $url;

    /**
     * Number of shops who sell this product.
     *
     * @var integer
     */
    public $shopCount;
}
