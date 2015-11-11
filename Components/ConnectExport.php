<?php

namespace ShopwarePlugins\Connect\Components;

use Shopware\Connect\SDK;
use ShopwarePlugins\Connect\Components\Marketplace\MarketplaceGateway;
use ShopwarePlugins\Connect\Components\Validator\ProductAttributesValidator;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Article\Article;
use Shopware\Models\Article\Detail;

class ConnectExport
{
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

    /**
     * @var Config
     */
    protected $configComponent;

    public function __construct(
        Helper $helper,
        SDK $sdk,
        ModelManager $manager,
        ProductAttributesValidator $productAttributesValidator,
        Config $configComponent
    )
    {
        $this->helper = $helper;
        $this->sdk = $sdk;
        $this->manager = $manager;
        $this->productAttributesValidator = $productAttributesValidator;
        $this->configComponent = $configComponent;
    }

    /**
     * Load article entity
     *
     * @param $id
     * @return null|\Shopware\Models\Article\Article
     */
    public function getArticleModelById($id)
    {
        return Shopware()->Models()->getRepository('Shopware\Models\Article\Article')->find($id);
    }

    /**
     * Load article detail entity
     *
     * @param $id
     * @return null|\Shopware\Models\Article\Detail
     */
    public function getArticleDetailById($id)
    {
        return Shopware()->Models()->getRepository('Shopware\Models\Article\Detail')->find($id);
    }

    /**
     * Helper function to mark a given array of source ids for connect update
     *
     * @param array $ids
     * @return array
     */
    public function export(array $ids)
    {
        $errors = array();
        if (count($ids) == 0) {
            return $errors;
        }

        $implodedIds = '"' . implode('","', $ids) . '"';
        $connectItems = Shopware()->Db()->fetchAll(
            "SELECT bi.article_id as articleId,
                    bi.article_detail_id as articleDetailId,
                    bi.export_status as exportStatus,
                    bi.export_message as exportMessage,
                    bi.source_id as sourceId,
                    a.name as title
            FROM s_plugin_connect_items bi
            LEFT JOIN s_articles a ON bi.article_id = a.id
            WHERE bi.source_id IN ($implodedIds);"
        );

        foreach ($connectItems as &$item) {
            $model = $this->getArticleDetailById($item['articleDetailId']);
            if($model === null) {
                continue;
            }

            $connectAttribute = $this->helper->getOrCreateConnectAttributeByModel($model);

            $prefix = $item['title'] ? $item['title'] . ': ' : '';
            if (empty($item['exportStatus']) || $item['exportStatus'] == 'delete' || $item['exportStatus'] == 'error') {
                $status = 'insert';
            } else {
                $status = 'update';
            }
            $connectAttribute->setExportStatus($status);
            $connectAttribute->setExportMessage(null);

            $categories = $this->helper->getConnectCategoryForProduct($item['articleId']);
            $connectAttribute->setCategory($categories);

            if (!$connectAttribute->getId()) {
                $this->manager->persist($connectAttribute);
            }
            $this->manager->flush($connectAttribute);

            try {
                $this->productAttributesValidator->validate($this->extractProductAttributes($model));
                if ($status == 'insert') {
                    $this->sdk->recordInsert($item['sourceId']);
                } else {
                    $this->sdk->recordUpdate($item['sourceId']);
                }
            } catch (\Exception $e) {
                $connectAttribute->setExportStatus('error');
                $connectAttribute->setExportMessage(
                    $e->getMessage() . "\n" . $e->getTraceAsString()
                );

                $errors[] = " &bull; " . $prefix . $e->getMessage();
                $this->manager->flush($connectAttribute);
            }
        }

        return $errors;
    }

    /**
     * Helper function to return export product ids
     * @return array
     */
    public function getExportArticlesIds()
    {
        $builder = Shopware()->Models()->createQueryBuilder();
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
     * Mark connect product for delete
     *
     * @param \Shopware\Models\Article\Article $article
     */
    public function syncDeleteArticle(Article $article)
    {
        $details = $article->getDetails();
        /** @var \Shopware\Models\Article\Detail $detail */
        foreach ($details as $detail) {
            $this->syncDeleteDetail($detail);
        }
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
        $attribute->setExportStatus('delete');
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
        $builder = Shopware()->Models()->createQueryBuilder();
        $builder->select(array('at.sourceId'))
            ->from('Shopware\CustomModels\Connect\Attribute', 'at')
            ->where('at.articleId = :articleId')
            ->setParameter(':articleId', $article->getId());
        $connectItems = $builder->getQuery()->getArrayResult();

        foreach($connectItems as $item) {
            $this->sdk->recordDelete($item['sourceId']);
        }

        $builder = Shopware()->Models()->createQueryBuilder();
        $builder->update('Shopware\CustomModels\Connect\Attribute', 'at')
            ->set('at.exportStatus', $builder->expr()->literal('delete'))
            ->where('at.articleId = :articleId')
            ->setParameter(':articleId', $article->getId());

        $builder->getQuery()->execute();
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