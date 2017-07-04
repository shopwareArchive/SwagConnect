<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Bundle\SearchBundleDBAL\ConditionHandler;

use Doctrine\DBAL\Connection;
use Shopware\Bundle\SearchBundle\ConditionInterface;
use Shopware\Bundle\SearchBundleDBAL\ConditionHandlerInterface;
use Shopware\Bundle\StoreFrontBundle\Struct\ShopContextInterface;
use Shopware\Bundle\SearchBundleDBAL\QueryBuilder;
use ShopwarePlugins\Connect\Bundle\SearchBundle\Condition\SupplierCondition;

/**
 * Class SupplierConditionHandler
 */
class SupplierConditionHandler implements ConditionHandlerInterface
{
    /**
     * @param ConditionInterface $condition
     * @return bool
     */
    public function supportsCondition(ConditionInterface $condition)
    {
        return ($condition instanceof SupplierCondition);
    }

    /**
     * @param ConditionInterface $condition
     * @param QueryBuilder $query
     * @param ShopContextInterface $context
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
