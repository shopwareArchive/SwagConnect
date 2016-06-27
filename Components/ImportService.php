<?php

namespace ShopwarePlugins\Connect\Components;

use ShopwarePlugins\Connect\Components\CategoryResolver\AutoCategoryResolver;
use Shopware\Components\Model\ModelManager;
use Shopware\Components\MultiEdit\Resource\Product;
use Shopware\CustomModels\Connect\ProductToRemoteCategoryRepository;
use Shopware\CustomModels\Connect\RemoteCategoryRepository;
use Shopware\Models\Article\Repository as ArticleRepository;
use Shopware\Models\Category\Repository as CategoryRepository;

class ImportService
{
    /**
     * @var ModelManager
     */
    private $manager;

    /**
     * @var \Shopware\Components\MultiEdit\Resource\Product
     */
    private $productResource;

    /**
     * @var \Shopware\Models\Article\Repository
     */
    private $categoryRepository;

    /**
     * @var ArticleRepository
     */
    private $articleRepository;

    /**
     * @var RemoteCategoryRepository
     */
    private $remoteCategoryRepository;

    /**
     * @var ProductToRemoteCategoryRepository
     */
    private $productToRemoteCategoryRepository;

    /**
     * @var CategoryResolver
     */
    private $autoCategoryResolver;

    /**
     * @var CategoryExtractor
     */
    private $categoryExtractor;

    public function __construct(
        ModelManager $manager,
        Product $productResource,
        CategoryRepository $categoryRepository,
        ArticleRepository$articleRepository,
        RemoteCategoryRepository $remoteCategoryRepository,
        ProductToRemoteCategoryRepository $productToRemoteCategoryRepository,
        AutoCategoryResolver $categoryResolver,
        CategoryExtractor $categoryExtractor
    )
    {
        $this->manager = $manager;
        $this->productResource = $productResource;
        $this->categoryRepository = $categoryRepository;
        $this->articleRepository = $articleRepository;
        $this->remoteCategoryRepository = $remoteCategoryRepository;
        $this->productToRemoteCategoryRepository = $productToRemoteCategoryRepository;
        $this->autoCategoryResolver = $categoryResolver;
        $this->categoryExtractor = $categoryExtractor;
    }

    public function findBothArticlesType($categoryId, $showOnlyConnectArticles = true, $limit = 10, $offset = 0)
    {
        if ($categoryId == 0) {
            return array();
        }
        return $this->productResource->filter($this->getAst($categoryId, $showOnlyConnectArticles), $offset, $limit);
    }

    /**
     * @param $categoryId
     * @return bool
     */
    public function hasCategoryChildren($categoryId)
    {
        return (bool) $this->categoryRepository->getChildrenCountList($categoryId);
    }

    public function assignCategoryToArticles($categoryId, array $articleIds)
    {
        $articles = $this->articleRepository->findBy(array('id' => $articleIds));

        if (empty($articles)) {
            throw new \RuntimeException('Invalid article ids');
        }

        /** @var \Shopware\Models\Category\Category $category */
        $category = $this->categoryRepository->find($categoryId);
        if (!$category) {
            throw new \RuntimeException('Invalid category id');
        }

        /** @var \Shopware\Models\Article\Article $article */
        foreach ($articles as $article) {
            $article->addCategory($category);
            $this->manager->persist($article);
            /** @var \Shopware\Models\Article\Detail $detail */
            foreach ($article->getDetails() as $detail) {
                $attribute = $detail->getAttribute();
                $attribute->setConnectMappedCategory(true);
                $this->manager->persist($attribute);
            }
        }

        $this->manager->flush();
    }

    /**
     * Unassign all categories from given article ids
     * Set connect_mapped_category flag in article
     * attributes to NULL
     *
     * @param array $articleIds
     * @throws \Doctrine\DBAL\ConnectionException
     * @throws \Exception
     */
    public function unAssignArticleCategories(array $articleIds)
    {
        if (!empty($articleIds)) {
            // cast all items in $articleIds to int
            // before use them in WHERE IN clause
            foreach ($articleIds as $key => $articleId) {
                $articleIds[$key] = (int)$articleId;
            }

            $connection = $this->manager->getConnection();
            $connection->beginTransaction();

            try {
                $attributeStatement = $connection->prepare(
                    'UPDATE s_articles_attributes SET connect_mapped_category = NULL WHERE articleID IN (' . implode(", ", $articleIds) . ')'
                );
                $attributeStatement->execute();

                $categoriesStatement = $this->manager->getConnection()->prepare('DELETE FROM s_articles_categories WHERE articleID IN (' . implode(", ", $articleIds) . ')');
                $categoriesStatement->execute();

                $categoryLogStatement = $this->manager->getConnection()->prepare('DELETE FROM s_articles_categories_ro WHERE articleID IN (' . implode(", ", $articleIds) . ')');
                $categoryLogStatement->execute();
                $connection->commit();

            } catch (\Exception $e) {
                $connection->rollBack();
                throw new \Exception($e->getMessage());
            }
        }
    }

    /**
     * Collect remote article ids by given category id
     *
     * @param int $localCategoryId
     * @return array
     */
    public function findRemoteArticleIdsByCategoryId($localCategoryId)
    {
        $connection = $this->manager->getConnection();
        $sql = 'SELECT sac.articleID
            FROM s_articles_categories sac
            LEFT JOIN s_articles_attributes saa ON sac.articleID = saa.articleID
            WHERE sac.categoryID = :categoryId AND saa.connect_mapped_category = 1';
        $rows = $connection->fetchAll($sql, array(':categoryId' => $localCategoryId));

        return array_map(function($row) {
            return $row['articleID'];
        }, $rows);
    }

    /**
     * Collect all child categories by given
     * remote category key and create same
     * categories structure as Shopware Connect structure.
     * Find all remote products which belong to these categories
     * and assign them.
     *
     * @param int $localCategoryId
     * @param string $remoteCategoryKey
     * @param string $remoteCategoryLabel
     * @return void
     */
    public function importRemoteCategory($localCategoryId, $remoteCategoryKey, $remoteCategoryLabel)
    {
        /** @var \Shopware\Models\Category\Category $localCategory */
        $localCategory = $this->categoryRepository->find((int)$localCategoryId);
        if (!$localCategory) {
            throw new \RuntimeException('Local category not found!');
        }

        /** @var \Shopware\CustomModels\Connect\RemoteCategory $remoteCategory */
        $remoteCategory = $this->remoteCategoryRepository->findOneBy(array('categoryKey' => $remoteCategoryKey));
        if (!$remoteCategory) {
            throw new \RuntimeException('Remote category not found!');
        }

        // collect his child categories and
        // generate remote category tree by given remote category
        $remoteCategoryChildren = $this->categoryExtractor->getRemoteCategoriesTree($remoteCategoryKey);
        $remoteCategoryNodes = array(
            array(
                'name' => $remoteCategoryLabel,
                'id' => $remoteCategoryKey,
                'leaf' => empty($remoteCategoryChildren) ? true : false,
                'children' => $remoteCategoryChildren,
            )
        );

        // create same category structure as Shopware Connect structure
        $this->autoCategoryResolver->convertTreeToEntities($remoteCategoryNodes, $localCategory);

        /** @var \Shopware\Models\Category\Category $category */
        foreach ($this->autoCategoryResolver->getLeafCollection() as $category) {
            $articleIds = $this->productToRemoteCategoryRepository->findArticleIdsByRemoteCategoryName($category->getName());

            while ($currentIdBatch = array_splice($articleIds, 0, 10)) {
                $articles = $this->articleRepository->findBy(array('id' => $currentIdBatch));
                /** @var \Shopware\Models\Article\Article $article */
                foreach ($articles as $article) {
                    $article->addCategory($category);
                    $attribute = $article->getAttribute();
                    $attribute->setConnectMappedCategory(true);
                    $this->manager->persist($article);
                    $this->manager->persist($attribute);
                }
                $this->manager->flush();
            }
        }
    }

    public function activateArticles(array $articleIds)
    {
        $articles = $this->articleRepository->findBy(array('id' => $articleIds));
        /** @var \Shopware\Models\Article\Article $article */
        foreach ($articles as $article) {
            $article->setActive(true);
            $this->manager->persist($article);
            /** @var \Shopware\Models\Article\Detail $detail */
            foreach($article->getDetails() as $detail) {
                $detail->setActive(1);
                $this->manager->persist($detail);
            }
        }

        $this->manager->flush();
    }

    /**
     * Helper function to create filter values
     * @param int $categoryId
     * @param boolean $showOnlyConnectArticles
     * @return array
     */
    private function getAst($categoryId, $showOnlyConnectArticles = true)
    {
        $ast = array (
            array (
                'type' => 'nullaryOperators',
                'token' => 'ISMAIN',
            ),
            array (
                'type' => 'boolOperators',
                'token' => 'AND',
            ),
            array (
                'type' => 'subOperators',
                'token' => '(',
            ),
            array (
                'type' => 'attribute',
                'token' => 'CATEGORY.PATH',
            ),
            array (
                'type' => 'binaryOperators',
                'token' => '=',
            ),
            array (
                'type' => 'values',
                'token' => '"%|' . $categoryId . '|%"',
            ),
            array (
                'type' => 'boolOperators',
                'token' => 'OR',
            ),
            array (
                'type' => 'attribute',
                'token' => 'CATEGORY.ID',
            ),
            array (
                'type' => 'binaryOperators',
                'token' => '=',
            ),
            array (
                'type' => 'values',
                'token' => $categoryId,
            ),
            array (
                'type' => 'subOperators',
                'token' => ')',
            ),
        );

        if ($showOnlyConnectArticles === true) {
            $ast = array_merge($ast, array(
                array (
                    'type' => 'boolOperators',
                    'token' => 'AND',
                ),
                array (
                    'type' => 'attribute',
                    'token' => 'ATTRIBUTE.CONNECTMAPPEDCATEGORY',
                ),
                array (
                    'type' => 'binaryOperators',
                    'token' => '!=',
                ),
                array (
                    'type' => 'values',
                    'token' => 'NULL',
                ),
            ));
        }

        return $ast;
    }
}