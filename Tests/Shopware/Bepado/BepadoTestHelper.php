<?php

namespace Tests\Shopware\Bepado;

use Shopware\Bepado\Components\BepadoExport;
use Shopware\Bepado\Components\ImageImport;

class BepadoTestHelper extends \Enlight_Components_Test_Plugin_TestCase
{

    /**
     * @return \Bepado\SDK\SDK
     */
    public function getSDK()
    {
        return Shopware()->Bootstrap()->getResource('BepadoSDK');
    }

    /**
     * @return string
     */
    public function getBepadoProductArticleId($sourceId, $shopId=3)
    {
        $id = Shopware()->Db()->fetchOne(
            'SELECT article_id FROM s_plugin_bepado_items WHERE source_id = ? and shop_id =  ? LIMIT 1',
            array($sourceId, $shopId)
        );
        return $id;
    }

    /**
     * @return \Shopware\Bepado\Components\Helper
     */
    public function getHelper()
    {
        return Shopware()->Plugins()->Backend()->SwagBepado()->getHelper();
    }


    public function callPrivate($class, $method, $args)
    {
        $method = new \ReflectionMethod(
            $class, $method
        );

        $method->setAccessible(true);

        return call_user_func(array($method, 'invoke', $args));
    }

    /**
     * @return BepadoExport
     */
    public function getBepadoExport()
    {
        return new BepadoExport(
            $this->getHelper(),
            $this->getSDK(),
            Shopware()->Models()
        );
    }


    /**
     * @return ImageImport
     */
    public function getImageImport()
    {
        return new ImageImport(
            Shopware()->Models(),
            $this->getHelper()
        );
    }

    public function changeCategoryBepadoMappingForCategoryTo($categoryId, $mapping)
    {
        $modelManager = Shopware()->Models();
        $categoryRepository = $modelManager->getRepository('Shopware\Models\Category\Category');
        $category = $categoryRepository->find($categoryId);

        if (!$category) {
            $this->fail('Could not find category with ID ' . $categoryId);
        }

        $attribute = $category->getAttribute() ?: new \Shopware\Models\Attribute\Category();
        $attribute->setBepadoImportMapping($mapping);
        $attribute->setBepadoExportMapping($mapping);
        $category->setAttribute($attribute);

        $modelManager->flush();
    }

    public static function dispatchRpcCall($service, $command, array $args)
    {
        $sdk = Shopware()->Bootstrap()->getResource('BepadoSDK');
        $refl = new \ReflectionObject($sdk);
        $property = $refl->getProperty('dependencies');
        $property->setAccessible(true);
        $deps = $property->getValue($sdk);
        $serviceRegistry = $deps->getServiceRegistry();
        $callable = $serviceRegistry->getService($service, $command);

        return call_user_func_array(array($callable['provider'], $callable['command']), $args);
    }

    protected function getProduct($withImage=false)
    {
        $number = rand(1, 999999999);
        $product =  new \Bepado\SDK\Struct\Product(array(
            'shopId' => 3,
            'revisionId' => time(),
            'sourceId' => $number,
            'ean' => $number,
            'url' => 'http://shopware.de',
            'title' => 'MassImport #'. $number,
            'shortDescription' => 'Ein Produkt aus Bepado',
            'longDescription' => 'Ein Produkt aus Bepado',
            'vendor' => 'Bepado',
            'price' => 9.99,
            'purchasePrice' => 6.99,
            'availability' => 100,
            'categories' => array('/bücher'),
        ));

        if ($withImage) {
            $product->images = array('http://lorempixel.com/400/200?'.$number);
        }

        return $product;
    }

    protected function getProducts($number=10, $withImage=false)
    {
        $products = array();
        for($i=0; $i<$number; $i++) {
            $products[] = $this->getProduct($withImage);
        }
        return $products;
    }

    protected function insertOrUpdateProducts($number, $withImage)
    {
        $commands = array();
        foreach ($this->getProducts($number, $withImage) as $product) {
            $commands[$product->sourceId] = new \Bepado\SDK\Struct\Change\ToShop\InsertOrUpdate(array(
                'product' => $product,
                'revision' => time(),
            ));
        }

        $this->dispatchRpcCall('products', 'toShop', array(
            $commands
        ));

        return array_keys($commands);
    }
}