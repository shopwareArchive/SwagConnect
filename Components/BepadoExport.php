<?php

namespace Shopware\Bepado\Components;

use Bepado\SDK\SDK;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Article\Article;
use Shopware\Models\Article\Detail;

class BepadoExport
{

    /** @var  Helper */
    protected $helper;
    /** @var  SDK */
    protected $sdk;
    /** @var  ModelManager */
    protected $manager;

    public function __construct(Helper $helper, SDK $sdk, ModelManager $manager)
    {
        $this->helper = $helper;
        $this->sdk = $sdk;
        $this->manager = $manager;
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
     * Helper function to mark a given array of source ids for bepado update
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
        $bepadoItems = Shopware()->Db()->fetchAll(
            "SELECT bi.article_id as articleId,
                    bi.article_detail_id as articleDetailId,
                    bi.export_status as exportStatus,
                    bi.export_message as exportMessage,
                    bi.source_id as sourceId,
                    a.name as title
            FROM s_plugin_bepado_items bi
            LEFT JOIN s_articles a ON bi.article_id = a.id
            WHERE bi.source_id IN ($implodedIds);"
        );

        foreach ($bepadoItems as &$item) {
            $model = $this->getArticleDetailById($item['articleDetailId']);
            if($model === null) {
                continue;
            }
            $bepadoAttribute = $this->helper->getOrCreateBepadoAttributeByModel($model);

            $prefix = $item['title'] ? $item['title'] . ': ' : '';
            if (empty($item['exportStatus']) || $item['exportStatus'] == 'delete' || $item['exportStatus'] == 'error') {
                $status = 'insert';
            } else {
                $status = 'update';
            }
            $bepadoAttribute->setExportStatus($status);
            $bepadoAttribute->setExportMessage(null);

            $category = $this->helper->getBepadoCategoryForProduct($item['articleId']);
            $bepadoAttribute->setCategory($category);

            if (!$bepadoAttribute->getId()) {
                $this->manager->persist($bepadoAttribute);
            }
            $this->manager->flush($bepadoAttribute);

            try {
                if ($status == 'insert') {
                    $this->sdk->recordInsert($item['sourceId']);
                } else {
                    $this->sdk->recordUpdate($item['sourceId']);
                }
            } catch (\Exception $e) {
                $bepadoAttribute->setExportStatus($status);
                $bepadoAttribute->setExportMessage(
                    $e->getMessage() . "\n" . $e->getTraceAsString()
                );

                $errors[] = " &bull; " . $prefix . $e->getMessage();
                $this->manager->flush($bepadoAttribute);
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
        $builder->from('Shopware\CustomModels\Bepado\Attribute', 'at');
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
        $sql = 'SELECT COUNT(*) FROM `bepado_change`';

        return (int)Shopware()->Db()->fetchOne($sql);
    }

    /**
     * Mark bepado product for delete
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
     * Mark single bepado product detail for delete
     *
     * @param \Shopware\Models\Article\Detail $detail
     */
    public function syncDeleteDetail(Detail $detail)
    {
        $attribute = $this->helper->getBepadoAttributeByModel($detail);
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
            ->from('Shopware\CustomModels\Bepado\Attribute', 'at')
            ->where('at.articleId = :articleId')
            ->setParameter(':articleId', $article->getId());
        $bepadoItems = $builder->getQuery()->getArrayResult();

        foreach($bepadoItems as $item) {
            $this->sdk->recordDelete($item['sourceId']);
        }

        $builder = Shopware()->Models()->createQueryBuilder();
        $builder->update('Shopware\CustomModels\Bepado\Attribute', 'at')
            ->set('at.exportStatus', $builder->expr()->literal('delete'))
            ->where('at.articleId = :articleId')
            ->setParameter(':articleId', $article->getId());

        $builder->getQuery()->execute();
    }
}