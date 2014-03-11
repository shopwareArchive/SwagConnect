<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version 1.1.142
 */

namespace Bepado\SDK\Struct\Verificator;

use Bepado\SDK\Struct\Verificator;
use Bepado\SDK\Struct\VerificatorDispatcher;
use Bepado\SDK\Struct;
use Bepado\SDK\Units;

/**
 * Visitor verifying integrity of struct classes
 *
 * @version 1.1.142
 */
class Product extends Verificator
{
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

        if (!is_numeric($struct->vat) || $struct->vat < 0 || $struct->vat > 1) {
            throw new \RuntimeException("Value added tax must be a number between 0 and 1.");
        }

        if (!is_array($struct->categories)) {
            throw new \RuntimeException("Invalid Datatype, Product#categories has to be an array.");
        }

        if (!is_array($struct->tags)) {
            throw new \RuntimeException("Invalid Datatype, Product#tags has to be an array.");
        }

        if ($struct->relevance < -1 || $struct->relevance > 1) {
            throw new \RuntimeException("Invalid Value, Product#relevance has to be -1,0,1");
        }

        if ($struct->deliveryWorkDays !== null && !is_numeric($struct->deliveryWorkDays)) {
            throw new \RuntimeException("Delivery Workdays needs to be either null or a number of days.");
        }

        if (!is_array($struct->attributes)) {
            throw new \RuntimeException("Product#attributes has to be an array.");
        }

        if (array_key_exists(Struct\Product::ATTRIBUTE_DIMENSION, $struct->attributes)) {
            if (!preg_match('(^(\d+x\d+x\d+)$)', $struct->attributes[Struct\Product::ATTRIBUTE_DIMENSION])) {
                throw new \RuntimeException(
                    "Product Dimensions Attribute has to be in format " .
                    "'Length x Width x Height' without spaces, i.e. 20x40x60"
                );
            }
        }

        if (array_key_exists(Struct\Product::ATTRIBUTE_WEIGHT, $struct->attributes)) {
            if (!is_numeric($struct->attributes[Struct\Product::ATTRIBUTE_WEIGHT])) {
                throw new \RuntimeException("Product Weight Attribute has to be a number.");
            }
        }

        if (array_key_exists(Struct\Product::ATTRIBUTE_UNIT, $struct->attributes)) {
            $this->validateUnit($struct);
        }
    }

    private function validateUnit($struct)
    {
        if (!Units::exists($struct->attributes[Struct\Product::ATTRIBUTE_UNIT])) {
            throw new \RuntimeException(sprintf(
                "Unit has to be one value from the available Bepado units, %s given",
                $struct->attributes[Struct\Product::ATTRIBUTE_UNIT]
            ));
        }

        if (!array_key_exists(Struct\Product::ATTRIBUTE_QUANTITY, $struct->attributes)) {
            throw new \RuntimeException(
                "When unit is given for product, specifying the quantity is required."
            );
        }

        if (!is_int($struct->attributes[Struct\Product::ATTRIBUTE_QUANTITY])) {
            throw new \RuntimeException(
                "Product Quantity Attribute has to be an integer."
            );
        }

        if (!array_key_exists(Struct\Product::ATTRIBUTE_REFERENCE_QUANTITY, $struct->attributes)) {
            throw new \RuntimeException(
                "When unit is given for product, specifying the reference quantity is required."
            );
        }

        if (!is_int($struct->attributes[Struct\Product::ATTRIBUTE_REFERENCE_QUANTITY])) {
            throw new \RuntimeException(
                "Product Quantity Attribute has to be an integer."
            );
        }
    }
}
