<?php

namespace ShopwarePlugins\Connect\Bundle\SearchBundle\Condition;

use Assert\Assertion;
use Shopware\Bundle\SearchBundle\ConditionInterface;

class SupplierCondition implements ConditionInterface
{
    /**
     * @var int[]
     */
    private $supplierIds;

    /**
     * SupplierCondition constructor.
     * @param array $supplierIds
     */
    public function __construct(array $supplierIds)
    {
        Assertion::allIntegerish($supplierIds);
        $this->supplierIds = array_map('intval', $supplierIds);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'supplier';
    }

    /**
     * @return int[]
     */
    public function getSupplierIds()
    {
        return $this->supplierIds;
    }
}
