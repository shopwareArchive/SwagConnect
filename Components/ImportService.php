<?php

namespace Shopware\Connect\Components;

use Shopware\Connect\Components\CategoryResolver\AutoCategoryResolver;
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

    public function findBothArticlesType($categoryId = null, $hideConnectArticle = false, $limit = 10, $offset = 0)
    {
        return $this->productResource->filter($this->getAst($categoryId, $hideConnectArticle), $offset, $limit);
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

        if ($remoteCategory->getLocalCategoryId() > 0) {
            throw new \RuntimeException('Remote category is already mapped!');
        }

        // collect his child categories and
        // generate remote category tree by given remote category
        $remoteCategoryChildren = $this->categoryExtractor->getRemoteCategoriesTree($remoteCategoryKey);
        $remoteCategoryTree = array(
            array(
                'name' => $remoteCategoryLabel,
                'id' => $remoteCategoryKey,
                'leaf' => empty($remoteCategoryChildren) ? true : false,
                'children' => $remoteCategoryChildren,
            )
        );

        // create same category structure as Shopware Connect structure
        $this->autoCategoryResolver->convertTreeToEntities($remoteCategoryTree, $localCategory);

        // collect only leaf categories
        $categoryNames = array();
        $categoryNames = $this->autoCategoryResolver->collectOnlyLeafCategories($remoteCategoryTree, $categoryNames);
        $categories = $this->categoryRepository->findBy(array(
            'name' => $categoryNames
        ));

        /** @var \Shopware\Models\Category\Category $category */
        foreach ($categories as $category) {
            $articleIds = $this->productToRemoteCategoryRepository->findArticleIdsByRemoteCategory($remoteCategory->getCategoryKey());
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
        }

        $this->manager->flush();
    }

    /**
     * Helper function to create filter values
     * @param int $categoryId
     * @param boolean $hideConnectArticles
     * @return array
     */
    private function getAst($categoryId, $hideConnectArticles = false)
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

        if ($hideConnectArticles === true) {
            $ast = array_merge($ast, array(
                array (
                    'type' => 'boolOperators',
                    'token' => 'AND',
                ),
                array (
                    'type' => 'attribute',
                    'token' => 'ATTRIBUTE.BEPADOMAPPEDCATEGORY',
                ),
                array (
                    'type' => 'unaryOperators',
                    'token' => 'ISNULL',
                ),
            ));
        }

        return $ast;
    }
}