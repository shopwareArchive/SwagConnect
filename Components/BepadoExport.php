<?php

namespace Shopware\Bepado\Components;

use Bepado\SDK\SDK;
use Shopware\Components\Model\ModelManager;

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
     * Helper function to mark a given array of product detail ids for bepado update
     *
     * @param array $ids
     * @return array
     */
    public function export(array $ids)
    {
        $errors = array();

        foreach($ids as $id) {
            /** @var \Shopware\Models\Article\Detail $articleDetail */
            $articleDetail = $this->getArticleDetailById($id);
            if($articleDetail === null) {
                continue;
            }

            $model = $articleDetail->getArticle();
            $prefix = $model && $model->getName() ? $model->getName() . ': ' : '';

            if($model === null) {
                continue;
            }
            $bepadoAttribute = $this->helper->getOrCreateBepadoAttributeByModel($articleDetail);

            $status = $bepadoAttribute->getExportStatus();
            if(empty($status) || $status == 'delete' || $status == 'error') {
                $status = 'insert';
            } else {
                $status = 'update';
            }
            $bepadoAttribute->setExportStatus(
                $status
            );
            $bepadoAttribute->setExportMessage(null);

            $category = $this->helper->getBepadoCategoryForProduct($id);
            $bepadoAttribute->setCategory(
                $category
            );

            if (!$bepadoAttribute->getId()) {
                $this->manager->persist($bepadoAttribute);
            }
            $this->manager->flush($bepadoAttribute);
            try {
                if($status == 'insert') {
                    $this->sdk->recordInsert($id);
                } else {
                    $this->sdk->recordUpdate($id);
                }
            } catch(\Exception $e) {
                $bepadoAttribute->setExportStatus(
                    'error'
                );
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

        $builder->andWhere('at.shopId IS NULL');

        $query = $builder->getQuery();
        $articles = $query->getArrayResult();

        $ids = array();
        foreach ($articles as $article) {
            $ids[] = $article['id'];
        }

        return $ids;
    }
}