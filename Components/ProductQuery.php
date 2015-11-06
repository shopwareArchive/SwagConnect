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

    public function getLocal(array $sourceIds)
    {
        return $this->localProductQuery->get($sourceIds);
    }

    public function getRemote(array $ids)
    {
        return $this->remoteProductQuery->get($ids);
    }

}
