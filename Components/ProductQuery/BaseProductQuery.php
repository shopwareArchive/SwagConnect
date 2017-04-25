<?php

namespace ShopwarePlugins\Connect\Components\ProductQuery;

use Shopware\Connect\Struct\Product;
use Shopware\Components\Model\ModelManager;
use Doctrine\ORM\Query\Expr\Join;


abstract class BaseProductQuery
{
    const IMAGE_PATH = '/media/image/';
    const LONG_DESCRIPTION_FIELD = 'longDescriptionField';
    const SHORT_DESCRIPTION_FIELD = 'shortDescriptionField';
    const CONNECT_DESCRIPTION_FIELD = 'connectDescriptionField';

    protected $manager;

    private $mediaService;

    public function __construct(ModelManager $manager, $mediaService = null)
    {
        $this->manager = $manager;
        $this->mediaService = $mediaService;
    }

    protected $attributeMapping = array(
        'weight' => Product::ATTRIBUTE_WEIGHT,
        'unit' => Product::ATTRIBUTE_UNIT,
        'referenceUnit' => 'ref_quantity',
        'purchaseUnit' => 'quantity'
    );


    /**
     * @return \Doctrine\ORM\QueryBuilder
     */
    abstract function getProductQuery();

    /**
     * @param $rows
     * @return array
     */
    abstract function getConnectProducts($rows);

    /**
     * Returns array of Product structs by given sourceIds
     *
     * @param array $sourceIds
     * @param int|null $shopId
     * @return array
     */
    public function get(array $sourceIds, $shopId = null)
    {
        $implodedIds = "'" . implode("','", $sourceIds) . "'";
        $builder = $this->getProductQuery();
        $builder->andWhere("at.sourceId IN ($implodedIds)");
        if ($shopId > 0) {
            $builder->andWhere("at.shopId = :shopId")
                    ->setParameter('shopId', $shopId);
        }

        $query = $builder->getQuery();

        return $this->getConnectProducts($query->getArrayResult());
    }

	/**
     * @param $detailId
     * @return array
     */
    protected function getPriceRanges($detailId)
    {
        $exportPriceCustomerGroup = $this->configComponent->getConfig('priceGroupForPriceExport', 'EK');
        $exportPriceColumn = $this->configComponent->getConfig('priceFieldForPriceExport');

        $columns = ['p.from', 'p.to', 'p.customerGroupKey'];

        if ($exportPriceColumn) {
            $columns[] = "p.{$exportPriceColumn} as price";
        }

        $builder = $this->manager->createQueryBuilder();
        $builder->select($columns)
            ->from('Shopware\Models\Article\Price', 'p')
            ->where('p.articleDetailsId = :detailId')
            ->andWhere('p.customerGroupKey = :groupKey')
            ->setParameter('detailId', $detailId)
            ->setParameter('groupKey', $exportPriceCustomerGroup);

        return $builder->getQuery()->getArrayResult();
    }

    /**
     * @param $articleId
     * @return array
     */
    protected function getProperties($articleId)
    {
        $columns = [
            'v.value',
            'v.position as valuePosition',
            'o.name as option',
            'o.filterable',
            'g.name as groupName',
            'g.position as groupPosition',
            'g.comparable',
            'g.sortMode',
        ];

        $builder = $this->manager->createQueryBuilder();
        $builder->select($columns)
            ->from('Shopware\Models\Property\Value', 'v')
            ->leftJoin('v.option', 'o')
            ->leftJoin('v.articles', 'a')
            ->leftJoin('a.propertyGroup', 'g')
            ->where('a.id = :articleId')
            ->setParameter('articleId', $articleId);

        return $builder->getQuery()->getArrayResult();
    }

    /**
     * @param $articleId
     * @return mixed
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    protected function attributeGroup($articleId)
    {
        $builder = $this->manager->createQueryBuilder();

        return $builder->select('g')
            ->from('Shopware\Models\Attribute\Article', 'attr')
            ->leftJoin('Shopware\Models\Article\Detail','d', Join::WITH, 'd.id = attr.articleDetailId')
            ->leftJoin('Shopware\Models\Property\Group','g', Join::WITH, 'g.id = attr.connectPropertyGroup')
            ->where('d.articleId = :articleId')
            ->andWhere('d.kind = 1')
            ->setParameter(':articleId', $articleId)
            ->getQuery()->getOneOrNullResult();
    }

    /**
     * @param $id
     * @return string[]
     */
    protected function getImagesById($id)
    {
        $builder = $this->manager->createQueryBuilder();
        $builder->select(array('i.path', 'i.extension', 'i.main', 'i.position'))
            ->from('Shopware\Models\Article\Image', 'i')
            ->where('i.articleId = :articleId')
            ->andWhere('i.parentId IS NULL')
            ->setParameter('articleId', $id)
            ->orderBy('i.main', 'ASC')
            ->addOrderBy('i.position', 'ASC');

        $query = $builder->getQuery();
        $query->setHydrationMode($query::HYDRATE_OBJECT);

        $images = $query->getArrayResult();

        $images = array_map(function($image) {
            return $this->getImagePath($image['path'] . '.' . $image['extension']);
        }, $images);


        return $images;
    }

    /**
     * Returns URL for the shopware image directory
     *
     * @param string $image
     * @return string
     */
    protected function getImagePath($image)
    {
        if ($this->mediaService) {
            return $this->mediaService->getUrl(self::IMAGE_PATH . $image);
        }

        $request = Shopware()->Front()->Request();

        if (!$request) {
            return '';
        }

        $imagePath = $request->getScheme() . '://'
            . $request->getHttpHost() . $request->getBasePath() . self::IMAGE_PATH . $image;

        return $imagePath;
    }

    /**
     * Prepares some common fields for local and remote products
     *
     * @param $row
     * @return mixed
     */
    public function prepareCommonAttributes($row)
    {
        if(isset($row['deliveryDate'])) {
            /** @var \DateTime $time */
            $time = $row['deliveryDate'];
            $row['deliveryDate'] = $time->getTimestamp();
        }

        // Fix categories
        if(is_string($row['category']) && strlen($row['category']) > 0) {
            $row['categories'] = json_decode($row['category'], true) ?: array();
        }
        unset($row['category']);

        // The SDK expects the weight to be numeric. So if it is NULL, we unset it here
        if ($row['weight'] === null) {
            unset($row['weight']);
        }

        // Make sure that there is a unit
        if ($row['unit'] === null) {
            unset($row['unit']);
        }

        // Fix attributes
        $row['attributes'] = array();
        foreach ($this->getAttributeMapping() as $swField => $connectField) {
            if (!array_key_exists($swField, $row)) {
                continue;
            }
            $row['attributes'][$connectField] = $row[$swField];
            unset($row[$swField]);
        }

        // Fix dimensions
        $row = $this->prepareProductDimensions($row);
        // Fix availability
        $row['availability'] = (int)$row['availability'];

        return $row;
    }

    /**
     * @param $row
     * @return mixed
     */
    public function prepareProductDimensions($row)
    {
        if (!empty($row['width']) && !empty($row['height']) && !empty($row['length'])) {
            $dimension = array(
                $row['length'], $row['width'], $row['height']
            );
            $row['attributes'][Product::ATTRIBUTE_DIMENSION] = implode('x', $dimension);
        }
        unset($row['width'], $row['height'], $row['length']);
        return $row;
    }

    public function getAttributeMapping()
    {
        return $this->attributeMapping;
    }
}

