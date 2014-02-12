<?php

namespace Tests\Shopware\Bepado;

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
    public function getBepadoProductArticleId()
    {
        $id = Shopware()->Db()->fetchOne(
            'SELECT article_id FROM s_plugin_bepado_items WHERE source_id IS NOT NULL LIMIT 1'
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

    public function changeCategoryBepadoMappingForCategoryTo($categoryId, $mapping)
    {
        $modelManager = Shopware()->Models();
        $categoryRepository = $modelManager->getRepository('Shopware\Models\Category\Category');
        $category = $categoryRepository->find($categoryId);

        if (!$category) {
            $this->fail('Could not find category with ID ' . $categoryId);
        }

        $attribute = $category->getAttribute() ?: new \Shopware\Models\Attribute\Category();
        $attribute->setBepadoMapping($mapping);
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
}