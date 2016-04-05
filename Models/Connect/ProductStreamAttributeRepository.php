<?php

namespace Shopware\CustomModels\Connect;

use \Shopware\Components\Model\ModelRepository;

/**
 * Class ProductStreamAttributeRepository
 * @package Shopware\CustomModels\Connect
 */
class ProductStreamAttributeRepository extends ModelRepository
{
    /**
     * @param ProductStreamAttribute $productStreamAttribute
     */
    public function save(ProductStreamAttribute $productStreamAttribute)
    {
        $this->getEntityManager()->persist($productStreamAttribute);
        $this->getEntityManager()->flush($productStreamAttribute);
    }

    /**
     * @return ProductStreamAttribute
     */
    public function create()
    {
        return new ProductStreamAttribute();
    }
}