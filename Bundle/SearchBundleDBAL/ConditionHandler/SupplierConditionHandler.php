<?php

namespace ShopwarePlugins\Connect\Bundle\SearchBundleDBAL\ConditionHandler;

use Doctrine\DBAL\Connection;
use Shopware\Bundle\SearchBundle\ConditionInterface;
use Shopware\Bundle\SearchBundleDBAL\ConditionHandlerInterface;
use Shopware\Bundle\StoreFrontBundle\Struct\ShopContextInterface;
use Shopware\Bundle\SearchBundleDBAL\QueryBuilder;
use ShopwarePlugins\Connect\Bundle\SearchBundle\Condition\SupplierCondition;

/**
 * @category  Shopware
 * @package   Shopware\Bundle\SearchBundleDBAL\ConditionHandler
 * @copyright Copyright (c) shopware AG (http://www.shopware.de)
 */
class SupplierConditionHandler implements ConditionHandlerInterface
{
    /**
     * {@inheritdoc}
     */
    public function supportsCondition(ConditionInterface $condition)
    {
        return ($condition instanceof SupplierCondition);
    }

    /**
     * {@inheritdoc}
     */
    public function generateCondition(
        ConditionInterface $condition,
        QueryBuilder $query,
        ShopContextInterface $context
    ) {
        $query->innerJoin(
            'product',
            's_plugin_connect_items',
            'ci',
            'ci.article_id = product.id
             AND ci.shop_id IN (:supplierIds)'
        );

        /** @var SupplierCondition $condition */
        $query->setParameter(
            ':supplierIds',
            $condition->getSupplierIds(),
            Connection::PARAM_INT_ARRAY
        );
    }
}
