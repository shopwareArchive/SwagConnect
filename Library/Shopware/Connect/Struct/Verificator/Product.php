<?php
/**
 * This file is part of the Shopware Connect SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Shopware\Connect\Struct\Verificator;

use Shopware\Connect\Struct\Verificator;
use Shopware\Connect\Struct\VerificatorDispatcher;
use Shopware\Connect\Struct;
use Shopware\Connect\Units;
use Shopware\Connect\ShippingRuleParser;
use Shopware\Connect\Languages;
use Shopware\Connect\SDK;

/**
 * Visitor verifying integrity of struct classes
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */
class Product extends Verificator
{
    /**
     * Product descriptions must be less than 16 000 000 bytes long
     */
    const DESCRIPTION_SIZE_LIMIT = 16000000;

    /**
     * Shipping rule parser
     *
     * @var ShippingRuleParser
     */
    private $parser;

    /**
     * @var int
     */
    private $priceType;

    /**
     * __construct
     *
     * @param ShippingRuleParser $parser
     * @return void
     */
    public function __construct(ShippingRuleParser $parser, $priceType)
    {
        $this->parser = $parser;
        $this->priceType = $priceType;
    }

    protected function verifyPriceExport(VerificatorDispatcher $dispatcher, Struct $struct)
    {
        switch ($this->priceType) {
            case SDK::PRICE_TYPE_PURCHASE:
                $this->verifyPurchasePrice($dispatcher, $struct);
                break;

            case SDK::PRICE_TYPE_RETAIL:
                $this->verifyRetailPrice($dispatcher, $struct);
                break;

            case SDK::PRICE_TYPE_BOTH:
                $this->verifyPrice($dispatcher, $struct);
                break;

            default:
                $this->fail("Exporting products is not allowed without a price type selected.");
                break;
        }
        $this->verifyFixedPrice($dispatcher, $struct);
    }

    protected function verifyPrice(VerificatorDispatcher $dispatcher, Struct $struct)
    {
        $this->verifyPurchasePrice($dispatcher, $struct);
        $this->verifyRetailPrice($dispatcher, $struct);
    }

    protected function verifyPurchasePrice(VerificatorDispatcher $dispatcher, Struct $struct)
    {
        if (empty($struct->purchasePrice) || $struct->purchasePrice <= 0) {
            $this->fail("The purchasePrice is not allowed to be 0 or smaller.");
        }
    }

    protected function verifyRetailPrice(VerificatorDispatcher $dispatcher, Struct $struct)
    {
        if (empty($struct->price) || $struct->price <= 0) {
            $this->fail("The price is not allowed to be 0 or smaller.");
        }
    }

    protected function verifyFixedPrice(VerificatorDispatcher $dispatcher, Struct $struct)
    {
        if ($struct->fixedPrice === true && $this->priceType == SDK::PRICE_TYPE_PURCHASE) {
            $this->fail("Fixed price is not allowed when export purchasePrice only");
        }
    }

    /**
     * Method to verify a structs integrity
     *
     * Throws a RuntimeException if the struct does not verify.
     *
     * @param \Shopware\Connect\Struct\VerificatorDispatcher $dispatcher
     * @param \Shopware\Connect\Struct $struct
     * @return void
     * @throws \RuntimeException
     */
    protected function verifyDefault(VerificatorDispatcher $dispatcher, Struct $struct)
    {
        /* @var $struct \Shopware\Connect\Struct\Product */

        foreach (array(
                'shopId',
                'sourceId',
                'currency',
                'availability',
                'vat',
                'relevance',
            ) as $property) {
            if ($struct->$property === null) {
                throw new \Shopware\Connect\Exception\VerificationFailedException("Property $property MUST be set in product.");
            }
        }

        foreach (array(
            'title',
            'sku',
            'shortDescription',
            'longDescription',
            ) as $property) {
            if (@iconv('UTF-8', 'UTF-8', $struct->$property) != $struct->$property) {
                throw new \Shopware\Connect\Exception\VerificationFailedException("Property $property MUST be UTF-8 encoded.");
            }
        }

        if (!is_array($struct->vendor)) {
            if (@iconv('UTF-8', 'UTF-8', $struct->vendor) != $struct->vendor) {
                throw new \Shopware\Connect\Exception\VerificationFailedException("Property vendor MUST be UTF-8 encoded.");
            }
            if (trim($struct->vendor) === '') {
                throw new \Shopware\Connect\Exception\VerificationFailedException("Property vendor MUST be non-empty.");
            }
        } else {
            if (empty($struct->vendor)) {
                throw new \Shopware\Connect\Exception\VerificationFailedException("Property vendor MUST be non-empty.");
            }
            if (!array_key_exists('name', $struct->vendor)) {
                throw new \Shopware\Connect\Exception\VerificationFailedException("Property vendor MUST have a name.");
            }

            $validKeys = array('name', 'url', 'logo_url', 'page_title', 'description');

            foreach (array_keys($struct->vendor) as $key) {
                if (!in_array($key, $validKeys)) {
                    throw new \Shopware\Connect\Exception\VerificationFailedException("Invalid key: $key for property vendor.");
                }
            }

            if (@iconv('UTF-8', 'UTF-8', $struct->vendor['name']) != $struct->vendor['name']) {
                throw new \Shopware\Connect\Exception\VerificationFailedException("Property vendor MUST be UTF-8 encoded.");
            }
            if (trim($struct->vendor['name']) === '') {
                throw new \Shopware\Connect\Exception\VerificationFailedException("The name of Property vendor MUST be non-empty.");
            }
        }

        foreach (array('title') as $property) {
            if (trim($struct->$property) === '') {
                throw new \Shopware\Connect\Exception\VerificationFailedException("Property $property MUST be non-empty.");
            }
        }

        if (!empty($struct->shipping)) {
            $this->parser->parseString($struct->shipping);
        }

        if (!is_numeric($struct->vat) || $struct->vat < 0 || $struct->vat > 1) {
            throw new \Shopware\Connect\Exception\VerificationFailedException("Value added tax must be a number between 0 and 1.");
        }

        if (!is_array($struct->categories)) {
            throw new \Shopware\Connect\Exception\VerificationFailedException("Invalid Datatype, Product#categories has to be an array.");
        }
        $this->verifyCategories($struct->categories);

        if (!is_array($struct->tags)) {
            throw new \Shopware\Connect\Exception\VerificationFailedException("Invalid Datatype, Product#tags has to be an array.");
        }

        if ($struct->relevance < -1 || $struct->relevance > 1) {
            throw new \Shopware\Connect\Exception\VerificationFailedException("Invalid Value, Product#relevance has to be -1,0,1");
        }

        if ($struct->deliveryWorkDays !== null && !is_numeric($struct->deliveryWorkDays)) {
            throw new \Shopware\Connect\Exception\VerificationFailedException("Delivery Workdays needs to be either null or a number of days.");
        }

        if (!is_array($struct->attributes)) {
            throw new \Shopware\Connect\Exception\VerificationFailedException("Product#attributes has to be an array.");
        }

        if (!is_array($struct->images)) {
            throw new \Shopware\Connect\Exception\VerificationFailedException("Product#images must be an array.");
        }

        if (is_array($struct->images) && array_values($struct->images) !== $struct->images) {
            throw new \Shopware\Connect\Exception\VerificationFailedException("Product#images must be numerically indexed starting with 0.");
        }

        if (!is_array($struct->variantImages)) {
            throw new \Shopware\Connect\Exception\VerificationFailedException("Product#variantImages must be an array.");
        }

        if (is_array($struct->variantImages) && array_values($struct->variantImages) !== $struct->variantImages) {
            throw new \Shopware\Connect\Exception\VerificationFailedException("Product#variantImages must be numerically indexed starting with 0.");
        }

        if (!is_array($struct->translations)) {
            throw new \Shopware\Connect\Exception\VerificationFailedException("Product#translations must be an array.");
        }
        foreach ($struct->translations as $language => $translation) {
            if (!is_string($language)) {
                throw new \Shopware\Connect\Exception\VerificationFailedException(
                    "The keys of Product#translations must be strings."
                );
            }
            if (!Languages::isValidLanguageCode($language)) {
                throw new \Shopware\Connect\Exception\VerificationFailedException(
                    "The keys of Product#translations must only be valid ISO 639-1 codes (e.g. 'de', 'es', ...)."
                );
            }
            if (!($translation instanceof Struct\Translation)) {
                throw new \Shopware\Connect\Exception\VerificationFailedException(
                    "Product#translations must contain only instances of \\Shopware\\Connect\\Struct\\Translation."
                );
            }

            $dispatcher->verify($translation);
        }

        if (array_key_exists(Struct\Product::ATTRIBUTE_DIMENSION, $struct->attributes)) {
            $dimensions = explode("x", $struct->attributes[Struct\Product::ATTRIBUTE_DIMENSION]);

            if (count(array_filter($dimensions, 'is_numeric')) !== 3) {
                throw new \Shopware\Connect\Exception\VerificationFailedException(
                    "Product Dimensions Attribute has to be in format " .
                    "'Length x Width x Height' without spaces, i.e. 20x40x60"
                );
            }
        }

        if (array_key_exists(Struct\Product::ATTRIBUTE_WEIGHT, $struct->attributes)) {
            if (!is_numeric($struct->attributes[Struct\Product::ATTRIBUTE_WEIGHT])) {
                throw new \Shopware\Connect\Exception\VerificationFailedException("Product Weight Attribute has to be a number.");
            }
        }

        foreach ($struct->attributes as $attributeKey => $attributeValue) {
            if (!is_null($attributeValue) && is_scalar($attributeValue) === false) {
                throw new \Shopware\Connect\Exception\VerificationFailedException("Attribute $attributeKey MUST be scalar value.");
            }
        }

        if (array_key_exists(Struct\Product::ATTRIBUTE_UNIT, $struct->attributes)) {
            $this->validateUnit($struct);
        }

        if ((strlen($struct->shortDescription) + strlen($struct->longDescription)) > self::DESCRIPTION_SIZE_LIMIT) {
            throw new \Shopware\Connect\Exception\VerificationFailedException("Product short and long description must be under 5 000 000 characters.");
        }

        if ($struct->minPurchaseQuantity < 1) {
            throw new \Shopware\Connect\Exception\VerificationFailedException("Product#minPurchaseQuantity must be positive, greater than 0.");
        }
    }

    private function validateUnit($struct)
    {
        if (!Units::exists($struct->attributes[Struct\Product::ATTRIBUTE_UNIT])) {
            throw new \Shopware\Connect\Exception\VerificationFailedException(sprintf(
                "Unit has to be one value from the available Shopware Connect units, %s given",
                $struct->attributes[Struct\Product::ATTRIBUTE_UNIT]
            ));
        }

        if (!array_key_exists(Struct\Product::ATTRIBUTE_QUANTITY, $struct->attributes)) {
            throw new \Shopware\Connect\Exception\VerificationFailedException(
                "When unit is given for product, specifying the quantity is required."
            );
        }

        if (!is_numeric($struct->attributes[Struct\Product::ATTRIBUTE_QUANTITY])) {
            throw new \Shopware\Connect\Exception\VerificationFailedException(
                "Product Quantity Attribute has to be a number."
            );
        }

        if (!array_key_exists(Struct\Product::ATTRIBUTE_REFERENCE_QUANTITY, $struct->attributes)) {
            throw new \Shopware\Connect\Exception\VerificationFailedException(
                "When unit is given for product, specifying the reference quantity is required."
            );
        }

        if (!is_numeric($struct->attributes[Struct\Product::ATTRIBUTE_REFERENCE_QUANTITY])) {
            throw new \Shopware\Connect\Exception\VerificationFailedException(
                "Product Quantity Attribute has to be a numeric."
            );
        }
    }

    private function verifyCategories(array $categories)
    {
        $parentCategoryMap = array();
        foreach ($categories as $category => $label) {
            $categoryParts = array_filter(explode('/', $category));

            $parentCategory = $category;
            while (count($categoryParts) > 1) {
                array_pop($categoryParts);

                $currentCategory = '/' . implode('/', $categoryParts);

                $parentCategoryMap[$currentCategory] = $parentCategory;
                $parentCategory = $currentCategory;
            }
        }

        foreach ($parentCategoryMap as $category => $parentCategory) {
            if (!isset($categories[$category])) {
                throw new \Shopware\Connect\Exception\VerificationFailedException(
                    sprintf(
                        'Product#categories must contain all parent categories. Parent category of "/Kleidung/Hosen" missing.',
                        $category
                    )
                );
            }
        }
    }
}
