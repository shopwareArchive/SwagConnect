<?php
namespace Bepado\Common\Struct;

use Bepado\Common\Struct;

abstract class Product extends Struct
{
    const STATE_INSERTED = 0;
    const STATE_NEW = 1;
    const STATE_INDEXED = 2;
    const STATE_MERGED = 4;

    /**
     * @var string
     */
    public $productId;

    /**
     * @var string
     */
    public $revisionId;

    /**
     * @var string
     */
    public $language = 'de_DE';

    /**
     * @var string
     */
    public $ean;

    /**
     * @var string
     */
    public $url;

    /**
     * @var string
     */
    public $title;

    /**
     * @var string
     */
    public $shortDescription;

    /**
     * @var string
     */
    public $longDescription;

    /**
     * @var string
     */
    public $vendor;

    /**
     * The value added tax for this product. The property must be set as a float
     * value.
     *
     * @var float
     */
    public $vat = 0.19;

    /**
     * @var float
     */
    public $price;

    /**
     * @var string
     */
    public $currency;

    /**
     * @var integer
     */
    public $availability;

    /**
     * @var string[]
     */
    public $images = array();

    /**
     * @var string[]
     */
    public $categories = array();

    /**
     * Contains additional attributes for this product. Use one of the constants
     * defined in this class to specify a an attribute:
     *
     * <code>
     * $product->attributes[\Bepado\SDK\Struct\Product::ATTRIBUTE_UNIT] = 'kg';
     * $product->attributes[\Bepado\SDK\Struct\Product::ATTRIBUTE_WEIGHT] = '1.0';
     * $product->attributes[\Bepado\SDK\Struct\Product::ATTRIBUTE_DIMENSION] = '20x30x40';
     * </code>
     *
     * @var string[]
     */
    public $attributes = array();

    /**
     * @param array $values
     */
    public function __construct(array $values = array())
    {
        parent::__construct($values);

        if (is_string($this->attributes)) {
            $this->attributes = (array) json_decode($this->attributes, true);
        }
    }
}
