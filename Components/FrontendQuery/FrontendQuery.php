<?php

namespace ShopwarePlugins\Connect\Components\FrontendQuery;

use Shopware\Components\Model\ModelManager;
use Shopware\Models\Article\Article;
use Shopware\Models\Article\Detail;
use Shopware\Models\Category\Category;
use Shopware\Models\Shop\Shop;

class FrontendQuery
{
    private $manager;

    public function __construct(ModelManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @param $articleId
     * @return Article|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getArticleById($articleId)
    {
        $queryBuilder = $this->manager->createQueryBuilder();
        $queryBuilder->select('a')
            ->from('Shopware\Models\Article\Article', 'a')
            ->where('a.id = :articleId')
            ->setParameter(':articleId', $articleId);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    /**
     * @param $detailId
     * @return Detail|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getArticleDetailById($detailId)
    {
        $queryBuilder = $this->manager->createQueryBuilder();
        $queryBuilder->select('ad')
            ->from('Shopware\Models\Article\Detail', 'ad')
            ->where('ad.id = :detailId')
            ->setParameter(':detailId', $detailId);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    /**
     * @param $shopId
     * @param bool $checkActive
     * @return Shop|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getShopById($shopId, $checkActive = false)
    {
        $queryBuilder = $this->manager->createQueryBuilder();
        $queryBuilder->select('s')
            ->from('\Shopware\Models\Shop\Shop', 's')
            ->where('s.id = :shopId')
            ->setParameter(':shopId', $shopId);

        if ($checkActive) {
            $queryBuilder->andWhere('s.active = 1');
        }

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    /**
     * @param $categoryId
     * @return Category|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getCategoryById($categoryId)
    {
        $queryBuilder = $this->manager->createQueryBuilder();
        $queryBuilder->select('c')
            ->from('Shopware\Models\Category\Category', 'c')
            ->where('c.id = :categoryId')
            ->setParameter(':categoryId', $categoryId);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    /**
     * @param $categoryId
     * @return Shop|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getShopByCategoryId($categoryId)
    {
        $queryBuilder = $this->manager->createQueryBuilder();
        $queryBuilder->select('s')
            ->from('\Shopware\Models\Shop\Shop', 's')
            ->where('s.categoryId = :categoryId')
            ->setParameter(':categoryId', $categoryId);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }
}
