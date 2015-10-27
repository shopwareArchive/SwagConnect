<?php

namespace Shopware\Bepado\Components;

use Shopware\Components\Model\ModelManager;
use Shopware\Components\MultiEdit\Resource\Product;
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

    private $articleRepository;

    public function __construct(
        ModelManager $manager,
        Product $productResource,
        CategoryRepository $categoryRepository,
        ArticleRepository$articleRepository
    )
    {
        $this->manager = $manager;
        $this->productResource = $productResource;
        $this->categoryRepository = $categoryRepository;
        $this->articleRepository = $articleRepository;
    }

    public function findBothArticlesType($categoryId = null, $limit = 10, $offset = 0)
    {
        return $this->productResource->filter($this->getAst($categoryId), $offset, $limit);
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
                $attribute->setBepadoMappedCategory(true);
                $this->manager->persist($attribute);
            }
        }

        $this->manager->flush();
    }

    private function getAst($categoryId)
    {
        return $ast = array (
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
    }
} 