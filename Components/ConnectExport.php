<?php

namespace ShopwarePlugins\Connect\Components;

use Doctrine\DBAL\DBALException;
use Shopware\Connect\SDK;
use Shopware\CustomModels\Connect\Attribute;
use ShopwarePlugins\Connect\Components\Marketplace\MarketplaceGateway;
use ShopwarePlugins\Connect\Components\Validator\ProductAttributesValidator;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Article\Article;
use Shopware\Models\Article\Detail;
use ShopwarePlugins\Connect\Components\ProductStream\ProductStreamsAssignments;
use ShopwarePlugins\Connect\Components\ErrorHandler;
use ShopwarePlugins\Connect\Struct\ExportList;
use ShopwarePlugins\Connect\Struct\SearchCriteria;

class ConnectExport
{
    const BATCH_SIZE = 200;

    /** @var  Helper */
    protected $helper;

    /** @var  SDK */
    protected $sdk;

    /** @var  ModelManager */
    protected $manager;

    /** @var ProductAttributesValidator  */
    protected $productAttributesValidator;

    /** @var  MarketplaceGateway */
    protected $marketplaceGateway;

    /** @var ErrorHandler */
    protected $errorHandler;

    /**
     * @var Config
     */
    protected $configComponent;

    public function __construct(
        Helper $helper,
        SDK $sdk,
        ModelManager $manager,
        ProductAttributesValidator $productAttributesValidator,
        Config $configComponent,
        ErrorHandler $errorHandler
    )
    {
        $this->helper = $helper;
        $this->sdk = $sdk;
        $this->manager = $manager;
        $this->productAttributesValidator = $productAttributesValidator;
        $this->configComponent = $configComponent;
        $this->errorHandler = $errorHandler;
    }

    /**
     * Load article entity
     *
     * @param $id
     * @return null|\Shopware\Models\Article\Article
     */
    public function getArticleModelById($id)
    {
        return $this->manager->getRepository('Shopware\Models\Article\Article')->find($id);
    }

    /**
     * Load article detail entity
     *
     * @param $id
     * @return null|\Shopware\Models\Article\Detail
     */
    public function getArticleDetailById($id)
    {
        return $this->manager->getRepository('Shopware\Models\Article\Detail')->find($id);
    }

    /**
     * Helper function to mark a given array of source ids for connect update
     *
     * There is a problem with flush when is called from life cycle event in php7,
     * this flag '$isEvent' is preventing the flush
     *
     * @param array $ids
     * @param ProductStreamsAssignments|null $streamsAssignments
     * @param boolean $isEvent
     * @return array
     */
    public function export(array $ids, ProductStreamsAssignments $streamsAssignments = null, $isEvent = false)
    {
        $errors = array();
        $connectItems = $this->fetchConnectItems($ids);

        foreach ($connectItems as &$item) {
            $model = $this->getArticleDetailById($item['articleDetailId']);
            if($model === null) {
                continue;
            }

            $connectAttribute = $this->helper->getOrCreateConnectAttributeByModel($model);
            $excludeInactiveProducts = $this->configComponent->getConfig('excludeInactiveProducts');
            if ($excludeInactiveProducts && !$model->getActive()) {
                $connectAttribute->setExportStatus(Attribute::STATUS_INACTIVE);
                $connectAttribute->setExportMessage(
                    Shopware()->Snippets()->getNamespace('backend/connect/view/main')->get(
                        'export/message/error_product_is_not_active',
                        'Produkt ist inaktiv',
                        true
                    )
                );
                $this->manager->persist($connectAttribute);

                //todo: Fix the flag $isEvent
                if (!$isEvent) {
                    $this->manager->flush($connectAttribute);
                }
                continue;
            }

            if (!$this->helper->isProductExported($connectAttribute)) {
                $status = Attribute::STATUS_INSERT;
                $connectAttribute->setExported(true);
            } else {
                $status = Attribute::STATUS_UPDATE;
            }
            $connectAttribute->setExportStatus($status);
            $connectAttribute->setExportMessage(null);

            $categories = $this->helper->getConnectCategoryForProduct($item['articleId']);
            $connectAttribute->setCategory($categories);

            if (!$connectAttribute->getId()) {
                $this->manager->persist($connectAttribute);
            }

            //todo: Fix the flag $isEvent
            if (!$isEvent) {
                $this->manager->flush($connectAttribute);
            }

            try {
                $this->productAttributesValidator->validate($this->extractProductAttributes($model));
                if ($status == Attribute::STATUS_INSERT) {
                    $this->sdk->recordInsert($item['sourceId']);
                } else {
                    $this->sdk->recordUpdate($item['sourceId']);
                }

                if ($this->helper->isMainVariant($item['sourceId']) &&
                    $streamsAssignments !== null &&
                    $streamsAssignments->getStreamsByArticleId($item['articleId']) !== null
                ) {
                    $this->sdk->recordStreamAssignment(
                        $item['sourceId'],
                        $streamsAssignments->getStreamsByArticleId($item['articleId'])
                    );
                }
            } catch (\Exception $e) {
                if ($this->errorHandler->isPriceError($e)) {
                    $connectAttribute->setExportStatus(Attribute::STATUS_ERROR_PRICE);
                    $connectAttribute->setExportMessage(
                        Shopware()->Snippets()->getNamespace('backend/connect/view/main')->get(
                            'export/message/error_price_status',
                            'There is an empty price field',
                            true
                        )
                    );
                } else {
                    $connectAttribute->setExportStatus(Attribute::STATUS_ERROR);
                    $connectAttribute->setExportMessage(
                        $e->getMessage() . "\n" . $e->getTraceAsString()
                    );
                }

                $this->errorHandler->handle($e);

                //todo: Fix the flag $isEvent
                if (!$isEvent) {
                    $this->manager->flush($connectAttribute);
                }
            }
        }

        return $this->errorHandler->getMessages();
    }

    /**
     * Fetch connect items
     * Default order is main variant first, after that regular variants.
     * This is needed, because first received variant with an unknown groupId in Connect
     * will be selected as main variant.
     *
     * @param array $sourceIds
     * @param boolean $orderByMainVariants
     * @return array
     */
    public function fetchConnectItems(array $sourceIds, $orderByMainVariants = true)
    {
        if (count($sourceIds) == 0) {
            return array();
        }

        $implodedIds = '"' . implode('","', $sourceIds) . '"';
        $query = "SELECT bi.article_id as articleId,
                    bi.article_detail_id as articleDetailId,
                    bi.export_status as exportStatus,
                    bi.export_message as exportMessage,
                    bi.source_id as sourceId,
                    a.name as title,
                    d.ordernumber as number
            FROM s_plugin_connect_items bi
            LEFT JOIN s_articles a ON bi.article_id = a.id
            LEFT JOIN s_articles_details d ON bi.article_detail_id = d.id
            WHERE bi.source_id IN ($implodedIds)";

        if ($orderByMainVariants === false) {
            $query .= ';';
            return Shopware()->Db()->fetchAll($query);
        }

        $query .= 'AND d.kind = ?;';
        $mainVariants = Shopware()->Db()->fetchAll($query, array(1));
        $regularVariants = Shopware()->Db()->fetchAll($query, array(2));

        return array_merge($mainVariants, $regularVariants);
    }

    /**
     * Helper function to return export product ids
     * @return array
     */
    public function getExportArticlesIds()
    {
        $builder = $this->manager->createQueryBuilder();
        $builder->from('Shopware\CustomModels\Connect\Attribute', 'at');
        $builder->join('at.article', 'a');
        $builder->join('a.mainDetail', 'd');
        $builder->leftJoin('d.prices', 'p', 'with', "p.from = 1 AND p.customerGroupKey = 'EK'");
        $builder->leftJoin('a.supplier', 's');
        $builder->leftJoin('a.tax', 't');

        $builder->select(array('a.id'));

        $builder->where("at.exportStatus = 'update' OR at.exportStatus = 'insert' OR at.exportStatus = 'error'");
        $builder->andWhere('at.shopId IS NULL');

        $query = $builder->getQuery();
        $articles = $query->getArrayResult();

        $ids = array();
        foreach ($articles as $article) {
            $ids[] = $article['id'];
        }

        return $ids;
    }

    /**
     * Helper function to count how many changes
     * are waiting to be synchronized
     *
     * @return int
     */
    public function getChangesCount()
    {
        $sql = 'SELECT COUNT(*) FROM `sw_connect_change`';

        return (int)Shopware()->Db()->fetchOne($sql);
    }

    /**
     * Mark single connect product detail for delete
     *
     * @param \Shopware\Models\Article\Detail $detail
     */
    public function syncDeleteDetail(Detail $detail)
    {
        $attribute = $this->helper->getConnectAttributeByModel($detail);
        $this->sdk->recordDelete($attribute->getSourceId());
        $attribute->setExportStatus(Attribute::STATUS_DELETE);
        $attribute->setExported(false);
        $this->manager->persist($attribute);
        $this->manager->flush($attribute);
    }

    /**
     * Mark all product variants for delete
     *
     * @param Article $article
     */
    public function setDeleteStatusForVariants(Article $article)
    {
        $builder = $this->manager->createQueryBuilder();
        $builder->select(array('at.sourceId'))
            ->from('Shopware\CustomModels\Connect\Attribute', 'at')
            ->where('at.articleId = :articleId')
            ->andWhere('at.exported = 1')
            ->setParameter(':articleId', $article->getId());
        $connectItems = $builder->getQuery()->getArrayResult();

        foreach($connectItems as $item) {
            $this->sdk->recordDelete($item['sourceId']);
        }

        $builder = $this->manager->createQueryBuilder();
        $builder->update('Shopware\CustomModels\Connect\Attribute', 'at')
            ->set('at.exportStatus', $builder->expr()->literal(Attribute::STATUS_DELETE))
            ->set('at.exported', 0)
            ->where('at.articleId = :articleId')
            ->setParameter(':articleId', $article->getId());

        $builder->getQuery()->execute();
    }

    /**
     * @param array $sourceIds
     * @param $status
     */
    public function updateConnectItemsStatus(array $sourceIds, $status)
    {
        if (empty($sourceIds)) {
            return;
        }

        $chunks = array_chunk($sourceIds, self::BATCH_SIZE);

        foreach ($chunks as $chunk) {
            $builder = $this->manager->getConnection()->createQueryBuilder();
            $builder->update('s_plugin_connect_items', 'ci')
                ->set('ci.export_status', ':status')
                ->where('source_id IN (:sourceIds)')
                ->setParameter('sourceIds', $chunk, \Doctrine\DBAL\Connection::PARAM_STR_ARRAY)
                ->setParameter('status', $status)
                ->execute();
        }
    }

    /**
     * @param SearchCriteria $criteria
     * @return ExportList
     */
    public function getExportList(SearchCriteria $criteria)
    {
        $customProductsTableExists = false;
        try {
            $builder = $this->manager->getConnection()->createQueryBuilder();
            $builder->select('id');
            $builder->from('s_plugin_custom_products_template');
            $builder->setMaxResults(1);
            $builder->execute()->fetch();

            $customProductsTableExists = true;
        } catch (DBALException $e) {
            // ignore it
            // custom products is not installed
        }

        $builder = $this->manager->getConnection()->createQueryBuilder();
        $builder->select(array(
            'a.id',
            'd.ordernumber as number',
            'd.inStock as inStock',
            'a.name as name',
            's.name as supplier',
            'a.active as active',
            't.tax as tax',
            'p.price * (100 + t.tax) / 100 as price',
            'i.category',
            'i.export_status as exportStatus',
            'i.export_message as exportMessage'
        ))
            ->from('s_plugin_connect_items', 'i')
            ->innerJoin('i', 's_articles', 'a', 'a.id = i.article_id')
            ->innerJoin('a', 's_articles_details', 'd', 'a.main_detail_id = d.id')
            ->leftJoin('d', 's_articles_prices', 'p', 'd.id = p.articledetailsID')
            ->leftJoin('a', 's_core_tax', 't', 'a.taxID = t.id')
            ->leftJoin('a', 's_articles_supplier', 's', 'a.supplierID = s.id')
            ->groupBy('i.article_id')
            ->where('i.shop_id IS NULL');

        if ($customProductsTableExists) {
            $builder->addSelect("IF(spcptpr.template_id > 0, 1, 0) as customProduct")
                    ->leftJoin('a', 's_plugin_custom_products_template_product_relation', 'spcptpr', 'a.id = spcptpr.article_id');
        }

        if ($criteria->search) {
            $builder->andWhere('d.number LIKE :search OR a.name LIKE :search OR supplier.name LIKE :search')
                ->setParameter('search', $criteria->search);
        }

        if ($criteria->categoryId) {
            $builder->innerJoin('a', 's_articles_categories', 'sac', 'a.id = sac.articleID')
                ->andWhere('sac.categoryID = :categoryId')
                ->setParameter('categoryId', $criteria->categoryId);
        }

        if ($criteria->supplierId) {
            $builder->andWhere('a.supplierID = :supplierId')
                ->setParameter('supplierId', $criteria->supplierId);
        }

        if ($criteria->exportStatus) {
            $builder->andWhere('items.export_status LIKE :status')
                ->setParameter('status', $criteria->exportStatus);
        }

        if ($criteria->active) {
            $builder->andWhere('a.active = :active')
                ->setParameter('active', $criteria->active);
        }

        if ($criteria->orderBy) {
            $builder->orderBy($criteria->orderBy, $criteria->orderByDirection);
        }

        $total = $builder->execute()->rowCount();

        $builder->setFirstResult($criteria->offset);
        $builder->setMaxResults($criteria->limit);

        $data = $builder->execute()->fetchAll();

        return new ExportList(array(
            'articles' => $data,
            'count' => $total,
        ));
    }

    public function clearConnectItems()
    {
        $this->deleteAllConnectProducts();
        $this->resetConnectItemsStatus();
    }

    /**
     * Deletes products hash
     */
    private function deleteAllConnectProducts()
    {
        $builder = $this->manager->getConnection()->createQueryBuilder();
        $builder->delete('sw_connect_product');
        $builder->execute();
    }

    /**
     * Resets all item status
     */
    private function resetConnectItemsStatus()
    {
        $builder = $this->manager->getConnection()->createQueryBuilder();
        $builder->update('s_plugin_connect_items', 'ci')
            ->set('export_status', ':exportStatus')
            ->set('revision', ':revision')
            ->set('exported', 0)
            ->setParameter('exportStatus', null)
            ->setParameter('revision', null);

        $builder->execute();
    }

    private function getMarketplaceGateway()
    {
        //todo@fixme: Implement better way to get MarketplaceGateway
        if (!$this->marketplaceGateway) {
            $this->marketplaceGateway = new MarketplaceGateway($this->manager);
        }

        return $this->marketplaceGateway;
    }

    /**
     * Extracts all marketplaces attributes from product
     *
     * @param Detail $detail
     * @return array
     */
    private function extractProductAttributes(Detail $detail)
    {
        $marketplaceAttributes = array();
        $marketplaceAttributes['purchaseUnit'] = $detail->getPurchaseUnit();
        $marketplaceAttributes['referenceUnit'] = $detail->getReferenceUnit();

        // marketplace attributes are available only for SEM shops
        if ($this->configComponent->getConfig('isDefault', true)) {
            return $marketplaceAttributes;
        }

        foreach ($this->getMarketplaceGateway()->getMappings() as $mapping) {
            $shopwareAttribute = $mapping['shopwareAttributeKey'];
            $getter = 'get' . ucfirst($shopwareAttribute);

            if (method_exists($detail->getAttribute(), $getter)) {
                $marketplaceAttributes[$shopwareAttribute] = $detail->getAttribute()->{$getter}();
            }
        }

        return $marketplaceAttributes;
    }
}