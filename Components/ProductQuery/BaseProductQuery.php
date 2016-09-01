<?php

namespace ShopwarePlugins\Connect\Components\ProductQuery;

use Shopware\Connect\Struct\Product;
use Shopware\Components\Model\ModelManager;


abstract class BaseProductQuery
{
    const IMAGE_PATH = '/media/image/';

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

    public function get(array $sourceIds)
    {
        $implodedIds = "'" . implode("','", $sourceIds) . "'";
        $builder = $this->getProductQuery();
        $builder->andWhere("at.sourceId IN ($implodedIds)");
        $query = $builder->getQuery();

        return $this->getConnectProducts($query->getArrayResult());
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
            unset ($row['weight']);
        }

        // Make sure that there is a unit
        if ($row['unit'] === null) {
            unset ($row['unit']);
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

