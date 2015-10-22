<?php
/**
 * Shopware 5
 * Copyright (c) shopware AG
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 */
namespace Shopware\Bepado\Components;
use Shopware\CustomModels\Bepado\AttributeRepository;

/**
 * Class CategoryExtractor
 * @package Shopware\CustomModels\Bepado
 */
class CategoryExtractor
{
    /**
     * @var \Shopware\CustomModels\Bepado\AttributeRepository
     */
    private $attributeRepository;

    /**
     * @var \Shopware\Bepado\Components\CategoryResolver
     */
    private $categoryResolver;

    public function __construct(AttributeRepository $attributeRepository, CategoryResolver $categoryResolver)
    {
        $this->attributeRepository = $attributeRepository;
        $this->categoryResolver = $categoryResolver;
    }

    /**
     * Collects categories
     * from imported Shopware Connect products
     */
    public function extractImportedCategories()
    {
        $categories = array();
        /** @var \Shopware\CustomModels\Bepado\Attribute $attribute */
        foreach ($this->attributeRepository->findRemoteArticleAttributes() as $attribute) {
            $categories = array_merge($categories, $attribute->getCategory());
        }

        return $this->convertTree($this->categoryResolver->generateTree($categories));
    }

    /**
     * Loads remote categories
     *
     * @param null $parent
     * @return array
     */
    public function getRemoteCategoriesTree($parent = null)
    {
        $sql = 'SELECT category_key, label FROM `s_plugin_bepado_categories`';
        if ($parent !== null) {
            $sql .= ' WHERE category_key LIKE ?';
            // filter only first child categories
            $rows = Shopware()->Db()->fetchPairs($sql, array($parent . '/%'));
            $rows = $this->convertTree($this->categoryResolver->generateTree($rows, $parent), false);
        } else {
            $rows = Shopware()->Db()->fetchPairs($sql);
            // filter only main categories
            $rows = $this->convertTree($this->categoryResolver->generateTree($rows), false);
        }

        return $rows;
    }

    /**
     * Converts categories tree structure
     * to be usable in ExtJS tree
     *
     * @param array $tree
     * @return array
     */
    private function convertTree(array $tree, $includeChildren = true)
    {
        $categories = array();
        foreach ($tree as $id => $category) {
            $children = array();
            if ($includeChildren === true && !empty($category['children'])) {
                $children = $this->convertTree($category['children'], $includeChildren);
            }
            $categories[] = array(
                'text' => $category['name'],
                'id' => $id,
                'leaf' => empty($category['children']) ? true : false,
                'children' => $children,
            );
        }

        return $categories;
    }
} 