<?php
/**
 * Shopware 4.0
 * Copyright © 2013 shopware AG
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
use Bepado\Common\Struct\Product\ShopProduct;
use Doctrine\ORM\QueryBuilder;
use Exception;
use Bepado\SDK\SDK;
use Bepado\SDK\Struct\Product,
    Shopware\Models\Article\Article as ProductModel,
    Shopware\Models\Category\Category as CategoryModel,
    Shopware\Components\Model\ModelManager,
    Doctrine\ORM\Query;
use Shopware\CustomModels\Bepado\Attribute as BepadoAttribute;
use Shopware\Models\Article\Detail as ProductDetail;
use Shopware\Models\Media\Media as MediaModel;
use Shopware\Models\Attribute\Media as MediaAttribute;
use Shopware\Models\Article\Image;
use Shopware\Bepado\Components\Utils\UnitMapper;

/**
 * @category  Shopware
 * @package   Shopware\Plugins\SwagBepado
 */
class Helper
{
    /**
     * @var ModelManager
     */
    private $manager;

    private $bepadoCategoryQuery;

    /** @var \Shopware\Bepado\Components\ProductQuery  */
    private $bepadoProductQuery;

    /**
     * @param ModelManager $manager
     * @param CategoryQuery
     * @param ProductQuery
     */
    public function __construct(
        ModelManager $manager,
        CategoryQuery $bepadoCategoryQuery,
        ProductQuery $bepadoProductQuery
    )
    {
        $this->manager = $manager;
        $this->bepadoCategoryQuery = $bepadoCategoryQuery;
        $this->bepadoProductQuery = $bepadoProductQuery;
    }

    /**
     * @return \Shopware\Models\Customer\Group
     */
    public function getDefaultCustomerGroup()
    {
        $repository = $this->manager->getRepository('Shopware\Models\Customer\Group');
        $customerGroup = $repository->findOneBy(array('key' => 'EK'));
        return $customerGroup;
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
        $builder->select(array('ba', 'a'));
        $builder->from('Shopware\CustomModels\Bepado\Attribute', 'ba');
        $builder->join('ba.article', 'a');
        $builder->join('a.mainDetail', 'd');
        $builder->leftJoin('d.attribute', 'at');

        $builder->where('ba.shopId = :shopId AND ba.sourceId = :sourceId');
        $builder->orWhere('d.number = :number');
        $query = $builder->getQuery();

        $query->setParameter('shopId', $product->shopId);
        $query->setParameter('sourceId', $product->sourceId);
        $query->setParameter('number', 'BP-' . $product->shopId . '-' . $product->sourceId);
        $result = $query->getResult(
            $query::HYDRATE_OBJECT,
            $mode
        );

        if (isset($result[0])) {
            $attribute = $result[0];
            return $attribute->getArticle();
        }

        return null;
    }

    public function getArticleDetailModelByProduct(Product $product, $mode = Query::HYDRATE_OBJECT)
    {
        $builder = $this->manager->createQueryBuilder();
        $builder->select(array('ba', 'd'));
        $builder->from('Shopware\CustomModels\Bepado\Attribute', 'ba');
        $builder->join('ba.articleDetail', 'd');
        $builder->leftJoin('d.attribute', 'at');

        $builder->where('ba.shopId = :shopId AND ba.sourceId = :sourceId');
        $builder->orWhere('d.number = :number');
        $query = $builder->getQuery();

        $query->setParameter('shopId', $product->shopId);
        $query->setParameter('sourceId', $product->sourceId);
        $query->setParameter('number', 'BP-' . $product->shopId . '-' . $product->sourceId);
        $result = $query->getResult(
            $query::HYDRATE_OBJECT,
            $mode
        );

        if (isset($result[0])) {
            /** @var \Shopware\CustomModels\Bepado\Attribute $attribute */
            $attribute = $result[0];
            return $attribute->getArticleDetail();
        }

        return null;
    }

    public function getBepadoArticleModel($sourceId, $shopId)
    {
        $builder = $this->manager->createQueryBuilder();
        $builder->select(array('ba', 'a'));
        $builder->from('Shopware\CustomModels\Bepado\Attribute', 'ba');
        $builder->join('ba.article', 'a');
        $builder->join('a.mainDetail', 'd');
        $builder->leftJoin('d.attribute', 'at');

        $builder->where('ba.shopId = :shopId AND ba.sourceId = :sourceId');
        $builder->orWhere('d.number = :number');
        $query = $builder->getQuery();

        $query->setParameter('shopId', $shopId);
        $query->setParameter('sourceId', $sourceId);
        $query->setParameter('number', 'BP-' . $shopId . '-' . $sourceId);
        $result = $query->getResult(
            $query::HYDRATE_OBJECT,
            Query::HYDRATE_OBJECT
        );

        if (isset($result[0])) {
            $attribute = $result[0];
            return $attribute->getArticle();
        }

        return null;
    }

    /**
     * Helper to update the bepado_items table
     */
    public function updateBepadoProducts()
    {
        // Insert new articles
        $sql = "
        INSERT INTO `s_plugin_bepado_items` (article_id, article_detail_id, source_id)
        SELECT a.id, ad.id, IF(ad.kind = 1, a.id, CONCAT(a.id, '-', ad.id)) as sourceID

        FROM s_articles a

        LEFT JOIN `s_articles_details` ad
        ON a.id = ad.articleId

        LEFT JOIN `s_plugin_bepado_items` bi
        ON bi.article_detail_id = ad.id


        WHERE a.id IS NOT NULL
        AND ad.id IS NOT NULL
        AND bi.id IS NULL
        ";

        $this->manager->getConnection()->exec($sql);

        // Delete removed articles from s_plugin_bepado_items
        $sql = '
        DELETE bi FROM `s_plugin_bepado_items`  bi

        LEFT JOIN `s_articles_details` ad
        ON ad.id = bi.article_detail_id

        WHERE ad.id IS NULL
        ';

        $this->manager->getConnection()->exec($sql);
    }


    /**
     * Returns a remote bepadoProduct e.g. for checkout maniputlations
     *
     * @param array $ids
     * @return array
     */
    public function getRemoteProducts(array $ids)
    {
        return $this->bepadoProductQuery->getRemote($ids);
    }

    /**
     * Returns a local bepadoProduct for export
     *
     * @param array $sourceIds
     * @return array
     */
    public function getLocalProduct(array $sourceIds)
    {
        return $this->bepadoProductQuery->getLocal($sourceIds);
    }

    /**
     * Does the current basket contain bepado products?
     *
     * @param $session
     * @return bool
     */
    public function hasBasketBepadoProducts($session, $userId = null)
    {
        $connection = $this->manager->getConnection();
        $sql = 'SELECT ob.articleID

            FROM s_order_basket ob

            INNER JOIN s_plugin_bepado_items bi
            ON bi.article_id = ob.articleID
            AND bi.shop_id IS NOT NULL

            WHERE ob.sessionID=?
            ';
        $whereClause = array($session);

        if ($userId > 0) {
            $sql .= ' OR userID=?';
            $whereClause[] = $userId;
        }

        $sql .= ' LIMIT 1';

        $result = $connection->fetchArray($sql, $whereClause);

        return !empty($result);
    }

    /**
     * Will return the bepadoAttribute for a given model. The model can be an Article\Article or Article\Detail
     *
     * @param $model ProductModel|ProductDetail
     * @return BepadoAttribute
     */
    public function getBepadoAttributeByModel($model)
    {
        $repository = $this->manager->getRepository('Shopware\CustomModels\Bepado\Attribute');

        if (!$model->getId()) {
            return false;
        }

        if ($model instanceof ProductModel) {
            if (!$model->getMainDetail()) {
                return false;
            }
            return $repository->findOneBy(array('articleDetailId' => $model->getMainDetail()->getId()));
        } elseif ($model instanceof ProductDetail) {
            return $repository->findOneBy(array('articleDetailId' => $model->getId()));
        }
    }

    /**
     * Helper method to create a bepado attribute on the fly
     *
     * @param $model
     * @return BepadoAttribute
     * @throws \RuntimeException
     */
    public function getOrCreateBepadoAttributeByModel($model)
    {
        $attribute = $this->getBepadoAttributeByModel($model);

        if (!$attribute) {
            $attribute = new BepadoAttribute();
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
                throw new \RuntimeException("Passed model needs to be an article or an article detail");
            }
            $this->manager->persist($attribute);
            $this->manager->flush($attribute);
        }

        return $attribute;
    }

    /**
     * Returns bepado attributes for article
     * and all variants.
     * If bepado attribute does not exist
     * it will be created.
     *
     * @param ProductModel $article
     * @return array
     */
    public function getOrCreateBepadoAttributes(ProductModel $article)
    {
        $attributes = array();
        /** @var \Shopware\Models\Article\Detail $detail */
        foreach ($article->getDetails() as $detail) {
            $attributes[] = $this->getOrCreateBepadoAttributeByModel($detail);
        }

        return $attributes;
    }

    /**
     * Generate sourceId
     *
     * @param ProductDetail $detail
     * @return int|string
     */
    public function generateSourceId(ProductDetail $detail)
    {
        if ($detail->getKind() == 1) {
            $sourceId = $detail->getArticle()->getId();
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
     * @return null|array
     */
    public function getBepadoCategoryForProduct($id)
    {
        return $this->getCategoryQuery()->getBepadoCategoryForProduct($id);
    }

    /**
     * @param Product $product
     * @return \Shopware\Models\Category\Category[]
     */
    public function getCategoriesByProduct(Product $product)
    {
        return $this->getCategoryQuery()->getCategoriesByProduct($product);
    }

    protected function getCategoryQuery()
    {
        return $this->bepadoCategoryQuery;
    }

    public function getMostRelevantBepadoCategory($categories)
    {
        usort(
            $categories,
            array(
                $this->getCategoryQuery()->getRelevanceSorter(),
                'sortBepadoCategoriesByRelevance'
            )
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
        return array(2 => 'shortDescription', 4 => 'longDescription', 8 => 'name', 16 => 'image', 32 => 'price', 64 => 'imageInitialImport');
    }

    /**
     * Retruns shopware unit entity
     * @param $unitKey
     * @return \Shopware\Models\Article\Unit
     */
    public function getUnit($unitKey)
    {
        $repository = $this->manager->getRepository('Shopware\Models\Article\Unit');

        return $repository->findOneBy(array('unit' => $unitKey));
    }

    /**
     * Clear article cache
     */
    public function clearArticleCache($articleId)
    {
        Shopware()->Events()->notify(
            'Shopware_Plugins_HttpCache_InvalidateCacheId',
            array('cacheId' => 'a' . $articleId)
        );
    }

    /**
     * Replace unit and ref quantity
     * @param $products
     * @return mixed
     */
    public function prepareBepadoUnit($products)
    {
        foreach ($products as &$p) {
            if ($p->attributes['unit']) {
                $configComponent = new Config($this->manager);
                /** @var \Shopware\Bepado\Components\Utils\UnitMapper $unitMapper */
                $unitMapper = new UnitMapper(
                    $configComponent,
                    $this->manager
                );

                $p->attributes['unit'] = $unitMapper->getBepadoUnit($p->attributes['unit']);
            }

            if ($p->attributes['ref_quantity']) {
                $intRefQuantity = (int)$p->attributes['ref_quantity'];
                if ($p->attributes['ref_quantity'] - $intRefQuantity <= 0.0001) {
                    $p->attributes['ref_quantity'] = $intRefQuantity;
                }
            }
        }

        return $products;
    }

    /**
     * Removes bepado reservation from session
     */
    public function clearBepadoReservation()
    {
        Shopware()->Session()->BepadoReservation = null;
    }

    /**
     * Collect sourceIds by given article ids
     *
     * @param array $articleIds
     * @return array
     */
    public function getArticleSourceIds(array $articleIds)
    {
        $articleRepository = Shopware()->Models()->getRepository('Shopware\Models\Article\Article');
        $sourceIds = array();
        foreach ($articleIds as $articleId) {

            /** @var \Shopware\Models\Article\Article $article */
            $article = $articleRepository->find($articleId);
            if (!$article) {
                continue;
            }

            $details = $article->getDetails();
            if (count($details) == 0) {
                continue;
            }

            /** @var \Shopware\Models\Article\Detail $detail */
            foreach ($details as $detail) {
                $bepadoAttribute = $this->getBepadoAttributeByModel($detail);
                if (!$bepadoAttribute) {
                    continue;
                }

                $sourceIds[] = $bepadoAttribute->getSourceId();
            }
        }

        return $sourceIds;
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

        return array(
            $articleId[0]
        );
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
     * @param $groupId
     * @param int $mode
     * @return null|ProductModel
     */
    public function getArticleByGroupId($groupId, $mode = Query::HYDRATE_OBJECT)
    {
        $builder = $this->manager->createQueryBuilder();
        $builder->select(array('ba', 'd'));
        $builder->from('Shopware\CustomModels\Bepado\Attribute', 'ba');
        $builder->join('ba.articleDetail', 'd');
        $builder->leftJoin('d.attribute', 'at');

        $builder->where('ba.groupId = :groupId AND ba.isMainVariant = 1');
        $query = $builder->getQuery();

        $query->setParameter('groupId', $groupId);
        $result = $query->getResult(
            $query::HYDRATE_OBJECT,
            $mode
        );

        if (isset($result[0])) {
            /** @var \Shopware\CustomModels\Bepado\Attribute $attribute */
            $attribute = $result[0];
            return $attribute->getArticle();
        }

        return null;
    }

}
