<?php

namespace Shopware\Bepado\Components\CategoryQuery;

use Shopware\Components\Model\ModelManager;
use Shopware\Bepado\Components\CategoryQuery;
use Doctrine\ORM\QueryBuilder;

abstract class SwQuery implements CategoryQuery
{
    /**
     * @var ModelManager
     */
    protected $manager;

    public function __construct(ModelManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @return \Shopware\Models\Article\Repository
     */
    protected function getArticleRepository()
    {
        $repository = $this->manager->getRepository(
            'Shopware\Models\Article\Article'
        );
        return $repository;
    }

    /**
     * @return \Shopware\Models\Category\Repository
     */
    protected function getCategoryRepository()
    {
        $repository = $this->manager->getRepository(
            'Shopware\Models\Category\Category'
        );
        return $repository;
    }
}
