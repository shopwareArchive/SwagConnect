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
namespace ShopwarePlugins\Connect\Components;
use Shopware\Connect\Gateway;
use Shopware\CustomModels\Connect\AttributeRepository;
use ShopwarePlugins\Connect\Components\RandomStringGenerator;

/**
 * Class CategoryExtractor
 * @package Shopware\CustomModels\Connect
 */
class CategoryExtractor
{
    /**
     * @var \Shopware\CustomModels\Connect\AttributeRepository
     */
    private $attributeRepository;

    /**
     * @var \ShopwarePlugins\Connect\Components\CategoryResolver
     */
    private $categoryResolver;

    /**
     * @var \Shopware\Connect\Gateway
     */
    private $configurationGateway;

    /**
     * @var \ShopwarePlugins\Connect\Components\RandomStringGenerator;
     */
    private $randomStringGenerator;

    /**
     * @param AttributeRepository $attributeRepository
     * @param CategoryResolver $categoryResolver
     * @param Gateway $configurationGateway
     * @param RandomStringGenerator $randomStringGenerator
     */
    public function __construct(
        AttributeRepository $attributeRepository,
        CategoryResolver $categoryResolver,
        Gateway $configurationGateway,
        RandomStringGenerator $randomStringGenerator
    )
    {

        $this->attributeRepository = $attributeRepository;
        $this->categoryResolver = $categoryResolver;
        $this->configurationGateway = $configurationGateway;
        $this->randomStringGenerator = $randomStringGenerator;
    }

    /**
     * Collects categories
     * from imported Shopware Connect products
     */
    public function extractImportedCategories()
    {
        $categories = array();
        /** @var \Shopware\CustomModels\Connect\Attribute $attribute */
        foreach ($this->attributeRepository->findRemoteArticleAttributes() as $attribute) {
            $categories = array_merge($categories, $attribute->getCategory());
        }

        return $this->convertTree($this->categoryResolver->generateTree($categories));
    }

    /**
     * Loads remote categories
     *
     * @param string|null $parent
     * @param boolean|null $includeChildren
     * @param boolean|null $excludeMapped
     * @return array
     */
    public function getRemoteCategoriesTree($parent = null, $includeChildren = false, $excludeMapped = false)
    {
        $sql = '
            SELECT pcc.category_key, pcc.label
            FROM `s_plugin_connect_categories` pcc
            INNER JOIN `s_plugin_connect_product_to_categories` pcptc
            ON pcptc.connect_category_id = pcc.id
            INNER JOIN `s_plugin_connect_items` pci
            ON pci.article_id = pcptc.articleID
            INNER JOIN `s_articles_attributes` ar
            ON ar.articleID = pci.article_id
        ';

        if ($parent !== null) {
            $sql .= ' WHERE pcc.category_key LIKE ?';
            $whereParams = array($parent . '/%');
            if ($excludeMapped === true) {
                $sql .= ' AND ar.connect_mapped_category IS NULL';
            }
            // filter only first child categories
            $rows = Shopware()->Db()->fetchPairs($sql, $whereParams);
            $rows = $this->convertTree($this->categoryResolver->generateTree($rows, $parent), $includeChildren);
        } else {
            if ($excludeMapped === true) {
                $sql .= ' WHERE ar.connect_mapped_category IS NULL';
            }
            $rows = Shopware()->Db()->fetchPairs($sql);
            // filter only main categories
            $rows = $this->convertTree($this->categoryResolver->generateTree($rows), $includeChildren);
        }

        return $rows;
    }

    /**
     * Collects remote categories by given stream and shopId
     *
     * @param string $stream
     * @param int $shopId
     * @return array
     */
    public function getRemoteCategoriesTreeByStream($stream, $shopId, $hideMapped = false)
    {
        $sql = 'SELECT category_key, label
                FROM `s_plugin_connect_categories` cat
                INNER JOIN `s_plugin_connect_product_to_categories` prod_to_cat ON cat.id = prod_to_cat.connect_category_id
                INNER JOIN `s_plugin_connect_items` attributes ON prod_to_cat.articleID = attributes.article_id
                INNER JOIN `s_articles_attributes` ar ON ar.articleID = attributes.article_id
                WHERE attributes.shop_id = ? AND attributes.stream = ?';

        if ($hideMapped) {
            $sql .= " AND ar.connect_mapped_category IS NULL";
        }

        $rows = Shopware()->Db()->fetchPairs($sql, array((int)$shopId, $stream));

        return $this->convertTree($this->categoryResolver->generateTree($rows), false);
    }

    /**
     * Collects supplier names as categories tree
     * @param null $excludeMapped
     * @return array
     */
    public function getMainNodes($excludeMapped = null)
    {
        // if parent is null collect shop names
        $shops = array();
        foreach ($this->configurationGateway->getConnectedShopIds() as $shopId) {
            if (!$this->hasShopItems($shopId, $excludeMapped)) {
                continue;
            }
            $configuration = $this->configurationGateway->getShopConfiguration($shopId);
            $shops[$shopId] = array(
                'name' => $configuration->displayName,
                'iconCls' => 'sc-tree-node-icon',
                'icon' => $configuration->logoUrl,
            );
        }

        $tree = $this->convertTree($shops, false);
        array_walk($tree, function(&$node) {
           $node['leaf'] = false;
        });

        return $tree;
    }

    /**
     * @param $shopId
     * @param bool $excludeMapped
     * @return bool
     */
    public function hasShopItems($shopId, $excludeMapped = false)
    {
        $sql = 'SELECT COUNT(pci.id)
                FROM `s_plugin_connect_items` pci
                INNER JOIN `s_articles_attributes` aa ON aa.articleID = pci.article_id
                WHERE pci.shop_id = ?
        ';

        if ($excludeMapped === true) {
            $sql .= ' AND aa.connect_mapped_category IS NULL';
        }

        $count = Shopware()->Db()->fetchOne($sql, array($shopId));

        return (bool) $count;
    }

    /**
     * Collects categories from products
     * by given shopId
     *
     * @param int $shopId
     * @param bool $includeChildren
     * @return array
     */
    public function extractByShopId($shopId, $includeChildren = false)
    {
        $sql = 'SELECT category_key, label
                FROM `s_plugin_connect_categories` cat
                INNER JOIN `s_plugin_connect_product_to_categories` prod_to_cat ON cat.id = prod_to_cat.connect_category_id
                INNER JOIN `s_plugin_connect_items` attributes ON prod_to_cat.articleID = attributes.article_id
                WHERE attributes.shop_id = ?';
        $rows = Shopware()->Db()->fetchPairs($sql, array($shopId));

        return $this->convertTree($this->categoryResolver->generateTree($rows), $includeChildren);
    }

    public function getStreamsByShopId($shopId)
    {
        $sql = 'SELECT DISTINCT(stream)
                FROM `s_plugin_connect_items` attributes
                WHERE attributes.shop_id = ?';
        $rows = Shopware()->Db()->fetchCol($sql, array($shopId));

        $streams = array();
        foreach ($rows as $streamName) {
            $id = sprintf('%s_stream_%s', $shopId, $streamName);
            $streams[$id] = array(
                'name' => $streamName,
                'iconCls' => 'sprite-product-streams',
            );
        }

        $tree = $this->convertTree($streams, false);
        array_walk($tree, function(&$node) {
            $node['leaf'] = false;
        });

        return $tree;
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
        foreach ($tree as $id => $node) {
            $children = array();
            if ($includeChildren === true && !empty($node['children'])) {
                $children = $this->convertTree($node['children'], $includeChildren);
            }

            if (strlen($node['name']) === 0) {
                continue;
            }

            $category = array(
                'name' => $node['name'],
                'id' => $this->randomStringGenerator->generate($id),
                'categoryId' => $id,
                'leaf' => empty($node['children']) ? true : false,
                'children' => $children,
                'cls' => 'sc-tree-node',
            );

            if (isset($node['iconCls'])) {
                $category['iconCls'] = $node['iconCls'];
            }

            if (isset($node['icon'])) {
                $category['icon'] = $node['icon'];
            }

            $categories[] = $category;
        }

        return $categories;
    }
} 