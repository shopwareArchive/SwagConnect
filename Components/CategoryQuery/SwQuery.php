<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Components\CategoryQuery;

use Shopware\Components\Model\ModelManager;
use Shopware\Models\Article\Article;
use Shopware\Models\Category\Category;
use Shopware\Models\Category\Category as CategoryModel;
use ShopwarePlugins\Connect\Components\CategoryQuery;
use Shopware\Models\Article\Repository as ArticleRepository;
use Shopware\Models\Category\Repository as CategoryRepository;

class SwQuery implements CategoryQuery
{
    /**
     * @var ModelManager
     */
    protected $manager;

    /**
     * @var RelevanceSorter
     */
    protected $relevanceSorter;

    /**
     * @param ModelManager $manager
     * @param RelevanceSorter $relevanceSorter
     */
    public function __construct(ModelManager $manager, RelevanceSorter $relevanceSorter)
    {
        $this->manager = $manager;
        $this->relevanceSorter = $relevanceSorter;
    }

    /**
     * @return RelevanceSorter
     */
    public function getRelevanceSorter()
    {
        return $this->relevanceSorter;
    }

    /**
     * @return ArticleRepository
     */
    protected function getArticleRepository()
    {
        return $this->manager->getRepository(
            Article::class
        );
    }

    /**
     * @return CategoryRepository
     */
    protected function getCategoryRepository()
    {
        return $this->manager->getRepository(
            CategoryModel::class
        );
    }

    /**
     * Return connect category mapping for the given product id
     *
     * @param $id
     * @return array
     */
    public function getConnectCategoryForProduct($id)
    {
        $builder = $this->manager->createQueryBuilder();
        $builder->select(['categories'])
            ->from('Shopware\Models\Category\Category', 'categories', 'categories.id')
            ->andWhere(':articleId MEMBER OF categories.articles')
            ->setParameters(['articleId' => (int) $id]);

        $result = $builder->getQuery()->getResult();
        if (empty($result)) {
            return [];
        }

        $categories = [];
        /** @var CategoryModel $category */
        foreach ($result as $category) {
            list($key, $name) = $this->extractCategory($category);
            $categories[$key] = $name;
            $parent = $category;
            while ($parent = $parent->getParent()) {
                if (!$parent->getParentId()) {
                    continue;
                }
                list($key, $name) = $this->extractCategory($parent);
                $categories[$key] = $name;
            }
        }

        return $categories;
    }

    /**
     * Returns category path and category name as array
     * @param Category $category
     * @return array
     */
    private function extractCategory(Category $category)
    {
        $path = $this->getCategoryRepository()->getPathById($category->getId(), 'name', ' > ');
        $key = $this->normalizeCategory($path);

        return [$key, $category->getName()];
    }

    /**
     * Normalize category name
     *
     * @param string $name
     * @return string
     */
    private function normalizeCategoryName($name)
    {
        return preg_replace('/[^\p{L}\p{N}]+/u', '_', strtolower($name));
    }

    /**
     * Convert a clear text category name into normalized format.
     *
     * @param string $categoryName
     * @return string
     */
    public function normalizeCategory($categoryName)
    {
        $path = preg_split('(\\s+>\\s+)', trim($categoryName));

        return '/' . implode(
            '/',
            array_map([$this, 'normalizeCategoryName'], $path)
        );
    }
}
