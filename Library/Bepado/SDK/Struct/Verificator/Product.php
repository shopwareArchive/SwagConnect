<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version $Revision$
 */

namespace Bepado\SDK\Struct\Verificator;

use Bepado\SDK\Struct\Verificator;
use Bepado\SDK\Struct\VerificatorDispatcher;
use Bepado\SDK\Struct;

/**
 * Visitor verifying integrity of struct classes
 *
 * @version $Revision$
 */
class Product extends Verificator
{
    /**
     * Categories
     *
     * @var array
     */
    protected $categories;

    /**
     * Construct from category mapping
     *
     * @param array $categories
     * @return void
     */
    public function __construct(array $categories)
    {
        $this->categories = $categories;
    }

    /**
     * Method to verify a structs integrity
     *
     * Throws a RuntimeException if the struct does not verify.
     *
     * @param \Bepado\SDK\Struct\VerificatorDispatcher $dispatcher
     * @param \Bepado\SDK\Struct $struct
     * @return void
     * @throws \RuntimeException
     */
    public function verify(VerificatorDispatcher $dispatcher, Struct $struct)
    {
        /* @var $struct \Bepado\SDK\Struct\Product */

        foreach (array(
                'shopId',
                'sourceId',
                'price',
                'purchasePrice',
                'currency',
                'availability',
                'vat',
                'relevance',
            ) as $property) {
            if ($struct->$property === null) {
                throw new \RuntimeException("Property $property MUST be set in product.");
            }
        }

        foreach (array(
            'title',
            'shortDescription',
            'longDescription',
            'vendor',
            ) as $property) {
            if (@iconv('UTF-8', 'UTF-8', $struct->$property) != $struct->$property) {
                throw new \RuntimeException("Property $property MUST be UTF-8 encoded.");
            }
        }

        if (empty($struct->purchasePrice) || $struct->purchasePrice <= 0) {
            throw new \RuntimeException("The purchasePrice is not allowed to be 0 or smaller.");
        }

        if (!in_array($struct->vat, array(0.0, 0.07, 0.19))) {
            throw new \RuntimeException("Only 0.00, 0.07 and 0.19 are allowed as value added tax.");
        }

        if (!is_array($struct->categories)) {
            throw new \RuntimeException("Invalid Datatype, Product#categories has to be an array.");
        }

        if (!count($struct->categories)) {
            throw new \RuntimeException("Assign at least one category to the product.");
        }

        if (!is_array($struct->tags)) {
            throw new \RuntimeException("Invalid Datatype, Product#tags has to be an array.");
        }

        if (count($unknown = array_diff($struct->categories, array_keys($this->categories)))) {
            throw new \RuntimeException("Unknown categories: " . implode(", ", $unknown));
        }

        if ($struct->relevance < -1 || $struct->relevance > 1) {
            throw new \RuntimeException("Invalid Value, Product#relevance has to be -1,0,1");
        }
    }
}
