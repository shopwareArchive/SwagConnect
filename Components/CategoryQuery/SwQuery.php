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
    /** @var  RelevanceSorter */
    protected $relevanceSorter;

    public function __construct(ModelManager $manager, RelevanceSorter $relevanceSorter)
    {
        $this->manager = $manager;
        $this->relevanceSorter = $relevanceSorter;
    }

    public function getRelevanceSorter()
    {
        return $this->relevanceSorter;
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
