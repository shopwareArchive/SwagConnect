<?php

namespace Shopware\Bepado\Components;

use Shopware\Components\Model\ModelManager;
use Doctrine\ORM\QueryBuilder;

class ProductQuery
{

    protected $manager;
    protected $localProductQuery;
    protected $remoteProductQuery;

    public function __construct($manager, ProductQuery\LocalProductQuery $localProductQuery, ProductQuery\RemoteProductQuery $remoteProductQuery)
    {
        $this->manager = $manager;
        $this->localProductQuery = $localProductQuery;
        $this->remoteProductQuery = $remoteProductQuery;
    }

    public function getLocal(array $ids)
    {
        return $this->localProductQuery->get($ids);
    }

    public function getRemote(array $ids)
    {
        return $this->remoteProductQuery->get($ids);
    }

}
