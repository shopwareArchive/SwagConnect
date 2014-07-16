<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Bepado\SDK\Struct\Verificator;

use Bepado\SDK\Struct\Verificator;
use Bepado\SDK\Struct\VerificatorDispatcher;
use Bepado\SDK\Struct;
use Bepado\SDK\Units;

/**
 * Visitor verifying integrity of struct classes
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
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
                throw new \Bepado\SDK\Exception\VerificationFailedException("Property $property MUST be set in product.");
            }
        }

        foreach (array(
            'title',
            'shortDescription',
            'longDescription',
            'vendor',
            ) as $property) {
            if (@iconv('UTF-8', 'UTF-8', $struct->$property) != $struct->$property) {
                throw new \Bepado\SDK\Exception\VerificationFailedException("Property $property MUST be UTF-8 encoded.");
            }
        }

        foreach (array('title', 'vendor') as $property) {
            if (trim($struct->$property) === '') {
                throw new \Bepado\SDK\Exception\VerificationFailedException("Property $property MUST be non-empty.");
            }
        }

        if (empty($struct->purchasePrice) || $struct->purchasePrice <= 0) {
            throw new \Bepado\SDK\Exception\VerificationFailedException("The purchasePrice is not allowed to be 0 or smaller.");
        }

        if (!is_numeric($struct->vat) || $struct->vat < 0 || $struct->vat > 1) {
            throw new \Bepado\SDK\Exception\VerificationFailedException("Value added tax must be a number between 0 and 1.");
        }

        if (!is_array($struct->categories)) {
            throw new \Bepado\SDK\Exception\VerificationFailedException("Invalid Datatype, Product#categories has to be an array.");
        }

        if (!is_array($struct->tags)) {
            throw new \Bepado\SDK\Exception\VerificationFailedException("Invalid Datatype, Product#tags has to be an array.");
        }

        if ($struct->relevance < -1 || $struct->relevance > 1) {
            throw new \Bepado\SDK\Exception\VerificationFailedException("Invalid Value, Product#relevance has to be -1,0,1");
        }

        if ($struct->deliveryWorkDays !== null && !is_numeric($struct->deliveryWorkDays)) {
            throw new \Bepado\SDK\Exception\VerificationFailedException("Delivery Workdays needs to be either null or a number of days.");
        }

        if (!is_array($struct->attributes)) {
            throw new \Bepado\SDK\Exception\VerificationFailedException("Product#attributes has to be an array.");
        }

        if (!is_array($struct->images)) {
            throw new \Bepado\SDK\Exception\VerificationFailedException("Product#images must be an array.");
        }

        if (is_array($struct->images) && array_values($struct->images) !== $struct->images) {
            throw new \Bepado\SDK\Exception\VerificationFailedException("Product#images must be numerically indexed starting with 0.");
        }

        if (array_key_exists(Struct\Product::ATTRIBUTE_DIMENSION, $struct->attributes)) {
            $dimensions = explode("x", $struct->attributes[Struct\Product::ATTRIBUTE_DIMENSION]);

            if (count(array_filter($dimensions, 'is_numeric')) !== 3) {
                throw new \Bepado\SDK\Exception\VerificationFailedException(
                    "Product Dimensions Attribute has to be in format " .
                    "'Length x Width x Height' without spaces, i.e. 20x40x60"
                );
            }
        }

        if (array_key_exists(Struct\Product::ATTRIBUTE_WEIGHT, $struct->attributes)) {
            if (!is_numeric($struct->attributes[Struct\Product::ATTRIBUTE_WEIGHT])) {
                throw new \Bepado\SDK\Exception\VerificationFailedException("Product Weight Attribute has to be a number.");
            }
        }

        if (array_key_exists(Struct\Product::ATTRIBUTE_UNIT, $struct->attributes)) {
            $this->validateUnit($struct);
        }
    }

    private function validateUnit($struct)
    {
        if (!Units::exists($struct->attributes[Struct\Product::ATTRIBUTE_UNIT])) {
            throw new \Bepado\SDK\Exception\VerificationFailedException(sprintf(
                "Unit has to be one value from the available Bepado units, %s given",
                $struct->attributes[Struct\Product::ATTRIBUTE_UNIT]
            ));
        }

        if (!array_key_exists(Struct\Product::ATTRIBUTE_QUANTITY, $struct->attributes)) {
            throw new \Bepado\SDK\Exception\VerificationFailedException(
                "When unit is given for product, specifying the quantity is required."
            );
        }

        if (!is_numeric($struct->attributes[Struct\Product::ATTRIBUTE_QUANTITY])) {
            throw new \Bepado\SDK\Exception\VerificationFailedException(
                "Product Quantity Attribute has to be a number."
            );
        }

        if (!array_key_exists(Struct\Product::ATTRIBUTE_REFERENCE_QUANTITY, $struct->attributes)) {
            throw new \Bepado\SDK\Exception\VerificationFailedException(
                "When unit is given for product, specifying the reference quantity is required."
            );
        }

        if (!is_int($struct->attributes[Struct\Product::ATTRIBUTE_REFERENCE_QUANTITY])) {
            throw new \Bepado\SDK\Exception\VerificationFailedException(
                "Product Quantity Attribute has to be an integer."
            );
        }
    }
}
