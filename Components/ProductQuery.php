<?php

namespace ShopwarePlugins\Connect\Components;

use Shopware\Components\Model\ModelManager;
use Doctrine\ORM\QueryBuilder;

class ProductQuery
{

    protected $localProductQuery;
    protected $remoteProductQuery;

    public function __construct(ProductQuery\LocalProductQuery $localProductQuery, ProductQuery\RemoteProductQuery $remoteProductQuery)
    {
        $this->localProductQuery = $localProductQuery;
        $this->remoteProductQuery = $remoteProductQuery;
    }

    /**
     * Returns array of local Product structs by given sourceIds
     *
     * @param array $sourceIds
     * @return \Shopware\Connect\Struct\Product[]
     */
    public function getLocal(array $sourceIds)
    {
        return $this->localProductQuery->get($sourceIds);
    }

    /**
     * Returns array of remote Product structs by given sourceIds and shopId
     *
     * @param array $sourceIds
     * @param int $shopId
     * @return \Shopware\Connect\Struct\Product[]
     */
    public function getRemote(array $sourceIds, $shopId)
    {
        return $this->remoteProductQuery->get($sourceIds, $shopId);
    }

}
