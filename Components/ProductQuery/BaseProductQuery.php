<?php

namespace Shopware\Bepado\Components\ProductQuery;

use Bepado\SDK\Struct\Product;
use Shopware\Components\Model\ModelManager;


abstract class BaseProductQuery
{

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
    abstract function getBepadoProducts($rows);

    public function get(array $ids)
    {
        $builder = $this->getProductQuery();
        $builder->where('a.id IN (:id)');
        $builder->setParameter('id', $ids);
        $query = $builder->getQuery();

        return $this->getBepadoProducts($query->getArrayResult());
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

        $imagePath = $this->getImagePath();
        $images = array_map(function($image) use ($imagePath) {
            return "{$imagePath}{$image['path']}.{$image['extension']}";
        }, $images);


        return $images;
    }

    /**
     * Returns URL for the shopware image directory
     *
     * @return string
     */
    protected function getImagePath()
    {
        $request = Shopware()->Front()->Request();

        if (!$request) {
            return '';
        }

        $imagePath = $request->getScheme() . '://'
            . $request->getHttpHost() . $request->getBasePath();
        $imagePath .= '/media/image/';

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
            $row['categories'] = array($row['category']);
        }
        unset($row['category']);

        // The SDK expects the weight to be numeric. So if it is NULL, we unset it here
        if ($row['weight'] === null) {
            unset ($row['weight']);
        }

        // Make sure that there is a unit
        if ($row['unit'] === null) {
            unset ($row['unit']);
        }

        // Fix attributes
        $row['attributes'] = array();
        foreach ($this->attributeMapping as $swField => $bepadoField) {
            if (!array_key_exists($swField, $row)) {
                continue;
            }
            $row['attributes'][$bepadoField] = $row[$swField];
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


}

