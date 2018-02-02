<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Components;

use Shopware\Connect\Struct\Product;
use Shopware\CustomModels\Connect\AttributeRepository;
use Shopware\Models\Article\Article as ProductModel;
use Shopware\Components\Model\ModelManager;
use Doctrine\ORM\Query;
use Shopware\CustomModels\Connect\Attribute as ConnectAttribute;
use Shopware\CustomModels\Connect\Attribute;
use Shopware\Models\Article\Detail as ProductDetail;
use Shopware\Models\Article\Unit;
use Shopware\Models\Customer\Group;
use ShopwarePlugins\Connect\Components\Utils\UnitMapper;
use ShopwarePlugins\Connect\Struct\ShopProductId;

/**
 * @category  Shopware
 * @package   Shopware\Plugins\SwagConnect
 */
class Helper
{
    /**
     * @var ModelManager
     */
    private $manager;

    /**
     * @var CategoryQuery
     */
    private $connectCategoryQuery;

    /**
     * @var ProductQuery
     */
    private $connectProductQuery;

    /**
     * @param ModelManager $manager
     * @param CategoryQuery
     * @param ProductQuery
     */
    public function __construct(
        ModelManager $manager,
        CategoryQuery $connectCategoryQuery,
        ProductQuery $connectProductQuery
    ) {
        $this->manager = $manager;
        $this->connectCategoryQuery = $connectCategoryQuery;
        $this->connectProductQuery = $connectProductQuery;
    }

    /**
     * @return Group
     */
    public function getDefaultCustomerGroup()
    {
        $repository = $this->manager->getRepository('Shopware\Models\Customer\Group');

        return $repository->findOneBy(['key' => 'EK']);
    }

    /**
     * Returns an article model for a given (sdk) product.
     *
     * @param Product $product
     * @param int $mode
     * @return null|ProductModel
     */
    public function getArticleModelByProduct(Product $product, $mode = Query::HYDRATE_OBJECT)
    {
        $builder = $this->manager->createQueryBuilder();
        $builder->select(['ba', 'a']);
        $builder->from('Shopware\CustomModels\Connect\Attribute', 'ba');
        $builder->join('ba.article', 'a');

        $builder->where('ba.shopId = :shopId AND ba.sourceId = :sourceId');
        $query = $builder->getQuery();

        $query->setParameter('shopId', $product->shopId);
        $query->setParameter('sourceId', (string) $product->sourceId);
        $result = $query->getResult(
            $mode
        );

        if (isset($result[0])) {
            $attribute = $result[0];

            return $attribute->getArticle();
        }

        return null;
    }

    /**
     * @param Product $product
     * @param int $mode
     * @return null|ProductDetail
     */
    public function getArticleDetailModelByProduct(Product $product, $mode = Query::HYDRATE_OBJECT)
    {
        $builder = $this->manager->createQueryBuilder();
        $builder->select(['ba', 'd']);
        $builder->from('Shopware\CustomModels\Connect\Attribute', 'ba');
        $builder->join('ba.articleDetail', 'd');
        $builder->leftJoin('d.attribute', 'at');
        $builder->where('ba.shopId = :shopId AND ba.sourceId = :sourceId');

        $query = $builder->getQuery();
        $query->setParameter('shopId', $product->shopId);
        $query->setParameter('sourceId', (string) $product->sourceId);

        $result = $query->getResult(
            $mode
        );

        if (isset($result[0])) {
            /** @var \Shopware\CustomModels\Connect\Attribute $attribute */
            $attribute = $result[0];

            return $attribute->getArticleDetail();
        }

        return null;
    }

    /**
     * Get article detail by his number
     *
     * @param string $number
     * @return null|ProductDetail
     */
    public function getDetailByNumber($number)
    {
        return $this->manager->getRepository(ProductDetail::class)->findOneBy(['number' => $number]);
    }

    public function getConnectArticleModel($sourceId, $shopId)
    {
        $builder = $this->manager->createQueryBuilder();
        $builder->select(['ba', 'a']);
        $builder->from('Shopware\CustomModels\Connect\Attribute', 'ba');
        $builder->join('ba.article', 'a');
        $builder->join('a.mainDetail', 'd');
        $builder->leftJoin('d.attribute', 'at');

        $builder->where('ba.shopId = :shopId AND ba.sourceId = :sourceId');
        $query = $builder->getQuery();

        $query->setParameter('shopId', $shopId);
        $query->setParameter('sourceId', (string) $sourceId);
        $result = $query->getResult(
            $query::HYDRATE_OBJECT
        );

        if (isset($result[0])) {
            $attribute = $result[0];

            return $attribute->getArticle();
        }

        return null;
    }

    /**
     * @param array $orderNumbers
     * @return array
     */
    public function getArticleIdsByNumber(array $orderNumbers)
    {
        $builder = $this->manager->getConnection()->createQueryBuilder();

        $rows = $builder->select('d.articleID as articleId')
            ->from('s_articles_details', 'd')
            ->where('d.ordernumber IN (:orderNumbers)')
            ->setParameter('orderNumbers', $orderNumbers, \Doctrine\DBAL\Connection::PARAM_STR_ARRAY)
            ->execute()
            ->fetchAll();

        return array_map(function ($row) {
            return $row['articleId'];
        }, $rows);
    }

    /**
     * Returns article detail model by
     * given sourceId and shopId
     *
     * @param string $sourceId
     * @param int $shopId
     * @return null|ProductDetail
     */
    public function getConnectArticleDetailModel($sourceId, $shopId)
    {
        $product = new Product(['sourceId' => $sourceId, 'shopId' => $shopId]);

        return $this->getArticleDetailModelByProduct($product);
    }

    /**
     * Helper to update the connect_items table
     */
    public function updateConnectProducts()
    {
        // Insert new articles
        $sql = "
        INSERT INTO `s_plugin_connect_items` (article_id, article_detail_id, source_id)
        SELECT a.id, ad.id, IF(ad.kind = 1, a.id, CONCAT(a.id, '-', ad.id)) as sourceID

        FROM s_articles a

        LEFT JOIN `s_articles_details` ad
        ON a.id = ad.articleId

        LEFT JOIN `s_plugin_connect_items` bi
        ON bi.article_detail_id = ad.id


        WHERE a.id IS NOT NULL
        AND ad.id IS NOT NULL
        AND bi.id IS NULL
        ";

        $this->manager->getConnection()->exec($sql);

        // Delete removed articles from s_plugin_connect_items
        $sql = '
        DELETE FROM `s_plugin_connect_items` 
        WHERE article_detail_id IS NULL AND NOT cron_update <=> 1
        ';

        $this->manager->getConnection()->exec($sql);
    }

    /**
     * Returns wether connect categories have to be recreated or not
     * @return bool
     */
    public function checkIfConnectCategoriesHaveToBeRecreated()
    {
        $configComponent = ConfigFactory::getConfigInstance();
        $result = $configComponent->getConfig('recreateConnectCategories');

        return $result === 0;
    }

    /**
     * Returns wether shopId has to be added to ConnectCategories
     * @return bool
     */
    public function checkIfShopIdHasToBeAddedToConnectCategories()
    {
        $configComponent = ConfigFactory::getConfigInstance();
        $result = $configComponent->getConfig('addShopIdToConnectCategories');

        return $result === 0;
    }

    /**
     * Returns a remote connectProduct e.g. for checkout maniputlations
     *
     * @param array $ids
     * @param int $shopId
     * @return array
     */
    public function getRemoteProducts(array $ids, $shopId)
    {
        return $this->connectProductQuery->getRemote($ids, $shopId);
    }

    /**
     * Returns a local connectProduct for export
     *
     * @param array $sourceIds
     * @return Product[]
     */
    public function getLocalProduct(array $sourceIds)
    {
        return $this->connectProductQuery->getLocal($sourceIds);
    }

    /**
     * Does the current basket contain connect products?
     *
     * @param $session
     * @return bool
     */
    public function hasBasketConnectProducts($session, $userId = null)
    {
        $connection = $this->manager->getConnection();
        $sql = 'SELECT ob.articleID

            FROM s_order_basket ob

            INNER JOIN s_plugin_connect_items bi
            ON bi.article_id = ob.articleID
            AND bi.shop_id IS NOT NULL

            WHERE ob.sessionID=?
            ';
        $whereClause = [$session];

        if ($userId > 0) {
            $sql .= ' OR userID=?';
            $whereClause[] = $userId;
        }

        $sql .= ' LIMIT 1';

        $result = $connection->fetchArray($sql, $whereClause);

        return !empty($result);
    }

    /**
     * Will return the connectAttribute for a given model. The model can be an Article\Article or Article\Detail
     *
     * @param $model ProductModel|ProductDetail
     * @return ConnectAttribute
     */
    public function getConnectAttributeByModel($model)
    {
        if (!$model->getId()) {
            return false;
        }
        $repository = $this->manager->getRepository('Shopware\CustomModels\Connect\Attribute');

        if ($model instanceof ProductModel) {
            if (!$model->getMainDetail()) {
                return false;
            }

            return $repository->findOneBy(['articleDetailId' => $model->getMainDetail()->getId()]);
        } elseif ($model instanceof ProductDetail) {
            return $repository->findOneBy(['articleDetailId' => $model->getId()]);
        }

        return false;
    }

    /**
     * Returns connectAttributes for all article details by given article object
     *
     * @param ProductModel $article
     * @return \Shopware\CustomModels\Connect\Attribute[]
     */
    public function getConnectAttributesByArticle(ProductModel $article)
    {
        $builder = $this->manager->createQueryBuilder();
        $builder->select(['connectAttribute', 'detail']);
        $builder->from('Shopware\CustomModels\Connect\Attribute', 'connectAttribute');
        $builder->innerJoin('connectAttribute.articleDetail', 'detail');

        $builder->where('connectAttribute.articleId = :articleId');
        $query = $builder->getQuery();

        $query->setParameter('articleId', $article->getId());

        return $query->getResult();
    }

    /**
     * Returns true when product is exported to Connect
     *
     * @param Attribute $connectAttribute
     * @return bool
     */
    public function isProductExported(Attribute $connectAttribute)
    {
        $status = $connectAttribute->getExportStatus();
        if ($connectAttribute->isExported()) {
            return true;
        }

        if ($status == Attribute::STATUS_INSERT) {
            return true;
        }

        if ($status == Attribute::STATUS_UPDATE) {
            return true;
        }

        if ($status == Attribute::STATUS_SYNCED) {
            return true;
        }

        return false;
    }

    /**
     * Verifies that at least one variant from
     * same article is exported.
     *
     * @param Attribute $connectAttribute
     * @return bool
     */
    public function hasExportedVariants(Attribute $connectAttribute)
    {
        $builder = $this->manager->getConnection()->createQueryBuilder();
        $builder->select('COUNT(spci.id)')
            ->from('s_plugin_connect_items', 'spci')
            ->where('spci.article_id = :articleId AND spci.export_status IN (:exportStatus) AND spci.shop_id IS NULL')
            ->setParameter('articleId', $connectAttribute->getArticleId(), \PDO::PARAM_INT)
            ->setParameter(
                ':exportStatus',
                [Attribute::STATUS_INSERT, Attribute::STATUS_UPDATE, Attribute::STATUS_SYNCED],
                \Doctrine\DBAL\Connection::PARAM_STR_ARRAY
            );

        return $builder->execute()->fetchColumn() > 0;
    }

    /**
     * Helper method to create a connect attribute on the fly
     *
     * @param $model
     * @throws \RuntimeException
     * @return ConnectAttribute
     */
    public function getOrCreateConnectAttributeByModel($model)
    {
        $attribute = $this->getConnectAttributeByModel($model);

        if (!$attribute) {
            $attribute = new ConnectAttribute();
            $attribute->setPurchasePriceHash('');
            $attribute->setOfferValidUntil('');
            $attribute->setStream('');

            if ($model instanceof ProductModel) {
                $attribute->setArticle($model);
                $attribute->setArticleDetail($model->getMainDetail());
                $attribute->setSourceId(
                    $this->generateSourceId($model->getMainDetail())
                );
            } elseif ($model instanceof ProductDetail) {
                $attribute->setArticle($model->getArticle());
                $attribute->setArticleDetail($model);
                $attribute->setSourceId(
                    $this->generateSourceId($model)
                );
            } else {
                throw new \RuntimeException('Passed model needs to be an article or an article detail');
            }
            $this->manager->persist($attribute);
            $this->manager->flush($attribute);
        }

        return $attribute;
    }

    /**
     * Returns connect attributes for article
     * and all variants.
     * If connect attribute does not exist
     * it will be created.
     *
     * @param ProductModel $article
     * @return array
     */
    public function getOrCreateConnectAttributes(ProductModel $article)
    {
        $attributes = [];
        /** @var \Shopware\Models\Article\Detail $detail */
        foreach ($article->getDetails() as $detail) {
            $attributes[] = $this->getOrCreateConnectAttributeByModel($detail);
        }

        return $attributes;
    }

    /**
     * Generate sourceId
     *
     * @param ProductDetail $detail
     * @return string
     */
    public function generateSourceId(ProductDetail $detail)
    {
        if ($detail->getKind() == 1) {
            $sourceId = (string) $detail->getArticle()->getId();
        } else {
            $sourceId = sprintf(
                '%s-%s',
                $detail->getArticle()->getId(),
                $detail->getId()
            );
        }

        return $sourceId;
    }

    /**
     * @param $id
     * @return array
     */
    public function getConnectCategoryForProduct($id)
    {
        return $this->connectCategoryQuery->getConnectCategoryForProduct($id);
    }

    public function getMostRelevantConnectCategory($categories)
    {
        usort(
            $categories,
            [
                $this->connectCategoryQuery->getRelevanceSorter(),
                'sortConnectCategoriesByRelevance'
            ]
        );

        return array_pop($categories);
    }

    /**
     * Defines the update flags
     *
     * @return array
     */
    public function getUpdateFlags()
    {
        return [2 => 'shortDescription', 4 => 'longDescription', 8 => 'name', 16 => 'image', 32 => 'price', 64 => 'imageInitialImport', 128 => 'additionalDescription', 256 => 'mainImage'];
    }

    /**
     * Returns shopware unit entity
     *
     * @param $unitKey
     * @return \Shopware\Models\Article\Unit
     */
    public function getUnit($unitKey)
    {
        $repository = $this->manager->getRepository('Shopware\Models\Article\Unit');

        return $repository->findOneBy(['unit' => $unitKey]);
    }

    /**
     * Clear article cache
     */
    public function clearArticleCache($articleId)
    {
        Shopware()->Events()->notify(
            'Shopware_Plugins_HttpCache_InvalidateCacheId',
            ['cacheId' => 'a' . $articleId]
        );
    }

    /**
     * Replace unit and ref quantity
     * @param $products
     * @return mixed
     */
    public function prepareConnectUnit($products)
    {
        foreach ($products as &$p) {
            if ($p->attributes['unit']) {
                $configComponent = ConfigFactory::getConfigInstance();
                /** @var \ShopwarePlugins\Connect\Components\Utils\UnitMapper $unitMapper */
                $unitMapper = new UnitMapper(
                    $configComponent,
                    $this->manager
                );

                $p->attributes['unit'] = $unitMapper->getConnectUnit($p->attributes['unit']);
            }

            if ($p->attributes['ref_quantity']) {
                $intRefQuantity = (int) $p->attributes['ref_quantity'];
                if ($p->attributes['ref_quantity'] - $intRefQuantity <= 0.0001) {
                    $p->attributes['ref_quantity'] = $intRefQuantity;
                }
            }
        }

        return $products;
    }

    /**
     * Removes connect reservation from session
     */
    public function clearConnectReservation()
    {
        Shopware()->Session()->connectReservation = null;
    }

    /**
     * Collect sourceIds by given article ids
     *
     * @param array $articleIds
     * @return array
     */
    public function getArticleSourceIds(array $articleIds)
    {
        if (empty($articleIds)) {
            return [];
        }

        /** @var AttributeRepository $repo */
        $repo = $this->manager->getRepository(ConnectAttribute::class);

        return array_merge(
            $repo->findSourceIds($articleIds, 1),
            $repo->findSourceIds($articleIds, 2)
        );
    }

    /**
     * Get ShopProductId struct by given article detail id
     * It contains product sourceId and shopId.
     * If $articleDetailId is local product, $shopProductId->shopId will be null.
     *
     * @param int $articleDetailId
     * @return ShopProductId
     */
    public function getShopProductId($articleDetailId)
    {
        $articleDetailId = (int) $articleDetailId;
        $builder = $this->manager->getConnection()->createQueryBuilder();
        $builder->select('items.source_id as sourceId, items.shop_id as shopId')
            ->from('s_plugin_connect_items', 'items')
            ->where('items.article_detail_id = :articleDetailIds')
            ->setParameter(':articleDetailIds', $articleDetailId);

        $result = $builder->execute()->fetch(\PDO::FETCH_ASSOC);

        return new ShopProductId($result);
    }

    /**
     * Check if given articleDetailId is remote product
     *
     * @param int $articleDetailId
     * @return bool
     */
    public function isRemoteArticleDetail($articleDetailId)
    {
        $articleDetailId = (int) $articleDetailId;
        $articleDetailRepository = $this->manager->getRepository('Shopware\Models\Article\Detail');
        /** @var \Shopware\Models\Article\Detail $detail */
        $detail = $articleDetailRepository->find($articleDetailId);
        if (!$detail) {
            return false;
        }

        $connectAttribute = $this->getConnectAttributeByModel($detail);
        if (!$connectAttribute) {
            return false;
        }

        return ($connectAttribute->getShopId() != null);
    }

    /**
     * Check if given articleDetailId is remote product
     *
     * @param int $articleDetailId
     * @return bool
     */
    public function isRemoteArticleDetailDBAL($articleDetailId)
    {
        $articleDetailId = (int) $articleDetailId;
        $builder = $this->manager->getConnection()->createQueryBuilder();
        $builder->select('items.shop_id')
            ->from('s_plugin_connect_items', 'items')
            ->where('items.article_detail_id = :articleDetailId')
            ->setParameter(':articleDetailId', $articleDetailId);

        return (bool) $builder->execute()->fetchColumn();
    }

    /**
     * Extract article ID and detail ID
     * from source ID
     *
     * @param $sourceId
     * @return array
     */
    public function explodeArticleId($sourceId)
    {
        $articleId = explode('-', $sourceId);

        if (isset($articleId[1]) && isset($articleId[1])) {
            return $articleId;
        }

        return [
            $articleId[0]
        ];
    }

    /**
     * Creates Shopware product model
     *
     * @param Product $product
     * @return ProductModel
     */
    public function createProductModel(Product $product)
    {
        //todo@sb: Add test
        $model = new ProductModel();
        $model->setActive(false);
        $model->setName($product->title);
        $this->manager->persist($model);

        return $model;
    }

    /**
     * Returns main article detail by given groupId
     *
     * @param $product
     * @param int $mode
     * @return null|ProductModel
     */
    public function getArticleByRemoteProduct(Product $product, $mode = Query::HYDRATE_OBJECT)
    {
        $builder = $this->manager->createQueryBuilder();
        $builder->select(['ba', 'd']);
        $builder->from('Shopware\CustomModels\Connect\Attribute', 'ba');
        $builder->join('ba.articleDetail', 'd');
        $builder->leftJoin('d.attribute', 'at');

        $builder->where('ba.groupId = :groupId AND ba.isMainVariant = 1 AND ba.shopId = :shopId');
        $query = $builder->getQuery();

        $query->setParameter('groupId', $product->groupId);
        $query->setParameter('shopId', $product->shopId);
        $result = $query->getResult(
            $mode
        );

        if (isset($result[0])) {
            /** @var \Shopware\CustomModels\Connect\Attribute $attribute */
            $attribute = $result[0];

            return $attribute->getArticle();
        }

        return null;
    }

    /**
     * @param int $articleId
     * @return array
     */
    public function getSourceIdsFromArticleId($articleId)
    {
        $rows = $this->manager->getConnection()->fetchAll(
            'SELECT source_id FROM s_plugin_connect_items WHERE article_id = ? AND exported = 1',
            [$articleId]
        );

        return array_map(function ($row) {
            return $row['source_id'];
        }, $rows);
    }

    /**
     * @param Unit $localUnit
     * @param string $remoteUnit
     */
    public function updateUnitInRelatedProducts(Unit $localUnit, $remoteUnit)
    {
        $statement = $this->manager->getConnection()->prepare('UPDATE s_articles_details sad
            LEFT JOIN s_articles_attributes saa ON sad.id = saa.articledetailsID
            SET sad.unitID = :unitId
            WHERE saa.connect_remote_unit = :remoteUnit');

        $statement->bindValue(':unitId', $localUnit->getId(), \PDO::PARAM_INT);
        $statement->bindValue(':remoteUnit', $remoteUnit, \PDO::PARAM_STR);

        $statement->execute();
    }

    /**
     * Checks whether given sourceId is main variant.
     * Works only with local products.
     * SourceIds pattern is articleId-variantId (58-142)
     *
     * For remote product check is_main_variant flag in
     * s_plugin_connect_items
     *
     * @param string $sourceId
     * @return bool
     */
    public function isMainVariant($sourceId)
    {
        $isMainVariant = $this->manager->getConnection()->fetchColumn(
            'SELECT d.kind
              FROM s_plugin_connect_items spci
              LEFT JOIN s_articles_details d ON spci.article_detail_id = d.id
              WHERE source_id = ?',
            [$sourceId]
        );

        if ($isMainVariant != 1) {
            return false;
        }

        return true;
    }

    public function getLocalArticleCount()
    {
        return $this->manager->getRepository(ConnectAttribute::class)->getLocalArticleCount();
    }

    /**
     * Recreates ConnectCategories wit the specified offset and batchsize
     * @param int $offset
     * @param int $batchsize
     */
    public function recreateConnectCategories($offset, $batchsize)
    {
        $result = $this->manager->getConnection()->executeQuery('SELECT `article_id`, `category`, `shop_id` FROM `s_plugin_connect_items` WHERE shop_id IS NOT NULL GROUP BY `article_id` ORDER BY `id` LIMIT ? OFFSET ?',
            [$batchsize, $offset],
            [\PDO::PARAM_INT, \PDO::PARAM_INT]
        );

        while ($row = $result->fetch()) {
            $categories = json_decode($row['category'], true);
            $countAssignedCategories = $this->manager->getConnection()->executeQuery('SELECT COUNT(`connect_category_id`) AS categories_count FROM s_plugin_connect_product_to_categories WHERE articleID = ?',
                [$row['article_id']]
            )->fetchColumn();

            if (count($categories) != $countAssignedCategories) {
                $shopId = $row['shop_id'];
                foreach ($categories as $categoryKey => $category) {
                    $selectedCategory = $this->manager->getConnection()->executeQuery('SELECT `id` FROM s_plugin_connect_categories WHERE category_key = ? AND shop_id = ?',
                        [$categoryKey, $shopId]);
                    if (!($res = $selectedCategory->fetch())) {
                        $this->manager->getConnection()->executeQuery('INSERT INTO s_plugin_connect_categories (category_key, label, shop_id) VALUES (?, ?, ?)',
                            [$categoryKey, $category, $shopId]);
                        $categoryId = (int) $this->manager->getConnection()->lastInsertId();
                    } else {
                        $categoryId = (int) $res['id'];
                    }
                    $selectedProductToCategory = $this->manager->getConnection()->executeQuery('SELECT COUNT(*) FROM s_plugin_connect_product_to_categories WHERE connect_category_id = ? AND articleID = ?',
                        [$categoryId, (int) $row['article_id']]
                    )->fetchColumn();
                    if ((int) $selectedProductToCategory === 0) {
                        $this->manager->getConnection()->executeQuery('INSERT INTO s_plugin_connect_product_to_categories (connect_category_id, articleID) VALUES (?, ?)',
                            [$categoryId, (int) $row['article_id']]
                            );
                    }
                }
            }
        }

        $totalCount = $this->getProductCountForCategoryRecovery();
        if ($batchsize + $offset >= $totalCount) {
            $configComponent = ConfigFactory::getConfigInstance();
            $configComponent->setConfig('recreateConnectCategories', 1);
        }
    }

    /**
     * @return int
     */
    public function getProductCountForCategoryRecovery()
    {
        return (int) $this->manager->getConnection()->executeQuery('
          SELECT COUNT(*) 
          FROM (
            SELECT COUNT(*) FROM `s_plugin_connect_items` WHERE shop_id IS NOT NULL GROUP BY `article_id`
          ) AS Z')->fetchColumn();
    }

    /**
     * adds the shopId to the ConnectCategoriesTable
     * @param int $offset
     * @param int $batchsize
     */
    public function addShopIdToConnectCategories($offset, $batchsize)
    {
        $result = $this->manager->getConnection()->executeQuery('SELECT `article_id`, `category`, `shop_id` FROM `s_plugin_connect_items` WHERE shop_id IS NOT NULL GROUP BY `article_id` ORDER BY `id` LIMIT ? OFFSET ?',
            [$batchsize, $offset],
            [\PDO::PARAM_INT, \PDO::PARAM_INT]
        );

        while ($row = $result->fetch()) {
            $categories = json_decode($row['category'], true);
            $this->manager->getConnection()->executeQuery('DELETE FROM s_plugin_connect_product_to_categories WHERE articleID = ?',
                [$row['article_id']]
            );

            $shopId = (int) $row['shop_id'];
            foreach ($categories as $categoryKey => $category) {
                $this->addShopIdToConnectCategory($categoryKey, $shopId, $category, $row);
            }
        }

        $totalCount = $this->getProductCountForCategoryRecovery();
        if ($batchsize + $offset >= $totalCount) {
            $configComponent = ConfigFactory::getConfigInstance();
            $configComponent->setConfig('addShopIdToConnectCategories', 1);
        }
    }

    /**
     * @param $categoryKey
     * @param $shopId
     * @param $category
     * @param $row
     */
    private function addShopIdToConnectCategory($categoryKey, $shopId, $category, $row)
    {
        $selectedCategory = $this->manager->getConnection()->executeQuery('SELECT `id` FROM s_plugin_connect_categories WHERE category_key = ? AND shop_id = ?',
            [$categoryKey, $shopId]);
        if (!($res = $selectedCategory->fetch())) {
            $categoryId = $this->createCategoryWithShopId($categoryKey, $shopId, $category);
        } else {
            $categoryId = (int) $res['id'];
        }
        $this->manager->getConnection()->executeQuery('INSERT INTO s_plugin_connect_product_to_categories (articleID, connect_category_id) VALUES (?, ?)',
            [$row['article_id'], $categoryId]
        );
    }

    /**
     * @param string $categoryKey
     * @param int $shopId
     * @param string $category
     * @return int
     */
    private function createCategoryWithShopId($categoryKey, $shopId, $category)
    {
        $selectedCategory = $this->manager->getConnection()->executeQuery('SELECT `id` FROM s_plugin_connect_categories WHERE category_key = ? AND shop_id IS NULL',
            [$categoryKey]);
        if (!($res = $selectedCategory->fetch())) {
            $this->manager->getConnection()->executeQuery('INSERT INTO s_plugin_connect_categories (category_key, label, shop_id) VALUES (?, ?, ?)',
                [$categoryKey, $category, $shopId]);
            $createdCategoryId = (int) $this->manager->getConnection()->lastInsertId();
            $this->assignLocalCategories($createdCategoryId, $categoryKey);

            return $createdCategoryId;
        }

        $this->manager->getConnection()->executeQuery('UPDATE s_plugin_connect_categories SET shop_id = ? WHERE id = ?',
            [$shopId, $res['id']]);

        return (int) $res['id'];
    }

    /**
     * @param int $createdCategoryId
     * @param string $categoryKey
     */
    private function assignLocalCategories($createdCategoryId, $categoryKey)
    {
        $originalCategoryId = $this->manager->getConnection()->fetchColumn('SELECT `id` FROM s_plugin_connect_categories WHERE category_key = ? AND id <> ?',
            [$categoryKey, $createdCategoryId]);
        $localCategories = $this->manager->getConnection()->executeQuery('SELECT `local_category_id` FROM s_plugin_connect_categories_to_local_categories WHERE remote_category_id = ?',
            [$originalCategoryId])->fetchAll(\PDO::FETCH_COLUMN);

        foreach ($localCategories as $localCategoryId) {
            $this->manager->getConnection()->executeQuery('INSERT INTO s_plugin_connect_categories_to_local_categories (remote_category_id, local_category_id) VALUES (?, ?)',
                [$createdCategoryId, $localCategoryId]);
        }
    }
}
