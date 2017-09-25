<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Tests\Integration\Bootstrap;

use ShopwarePlugins\Connect\Bootstrap\Update;
use ShopwarePlugins\Connect\Tests\DatabaseTestCaseTrait;
use ShopwarePlugins\Connect\Components\Logger;

class UpdateTest extends \PHPUnit_Framework_TestCase
{
    use DatabaseTestCaseTrait;

    private $update;
    private $manager;

    public function setUp()
    {
        $this->manager = Shopware()->Models();
        $this->update = new Update(
            new \Shopware_Plugins_Backend_SwagConnect_Bootstrap('SwagConnect'),
            Shopware()->Models(),
            Shopware()->Db(),
            new Logger(Shopware()->Db()),
            '1.1.4'
        );
    }

    public function testRecreateRemoteCategoriesAndProductAssignmentsRestoresAllCategories()
    {
        $this->manager->getConnection()->executeQuery('DELETE FROM s_plugin_connect_categories');
        $this->manager->getConnection()->executeQuery('DELETE FROM s_plugin_connect_product_to_categories');
        $this->manager->getConnection()->executeQuery('DELETE FROM s_plugin_connect_items');

        $categories = [
            '/deutsch' => 'Deutsch',
            '/deutsch/test1' => 'Test 1',
            '/deutsch/test1/test11' => 'Test 1.1',
            '/deutsch/test2' => 'Test 2',
        ];
        $categoriesJSON = json_encode($categories);

        $this->manager->getConnection()->executeQuery('INSERT INTO s_plugin_connect_items (article_id, exported, category) VALUES (?, ?, ?)',
            [3, null, $categoriesJSON]
        );

        $this->invokeMethod($this->update, 'recreateRemoteCategoriesAndProductAssignments');

        $result = $this->manager->getConnection()->executeQuery('SELECT COUNT(*) AS number FROM s_plugin_connect_categories')->fetch();
        $this->assertEquals('4', $result['number']);

        $result = $this->manager->getConnection()->executeQuery('SELECT COUNT(*) AS number FROM s_plugin_connect_product_to_categories WHERE articleID = 3')->fetch();
        $this->assertEquals('4', $result['number']);
    }

    public function testRecreateRemoteCategoriesAndProductAssignmentsAssignesAllCategories()
    {
        $this->manager->getConnection()->executeQuery('DELETE FROM s_plugin_connect_categories');
        $this->manager->getConnection()->executeQuery('DELETE FROM s_plugin_connect_product_to_categories');
        $this->manager->getConnection()->executeQuery('DELETE FROM s_plugin_connect_items');

        $categories = [
            '/deutsch' => 'Deutsch',
            '/deutsch/test1' => 'Test 1',
            '/deutsch/test1/test11' => 'Test 1.1',
            '/deutsch/test2' => 'Test 2',
        ];

        foreach ($categories as $categoryKey => $category) {
            $this->manager->getConnection()->executeQuery('INSERT INTO s_plugin_connect_categories (category_key, label) VALUES (?, ?)',
                [$categoryKey, $category]);
        }

        $categoriesJSON = json_encode($categories);

        $this->manager->getConnection()->executeQuery('INSERT INTO s_plugin_connect_items (article_id, exported, category) VALUES (?, ?, ?)',
            [3, null, $categoriesJSON]
        );

        $this->invokeMethod($this->update, 'recreateRemoteCategoriesAndProductAssignments');

        $result = $this->manager->getConnection()->executeQuery('SELECT COUNT(*) AS number FROM s_plugin_connect_categories')->fetch();
        $this->assertEquals('4', $result['number']);

        $result = $this->manager->getConnection()->executeQuery('SELECT COUNT(*) AS number FROM s_plugin_connect_product_to_categories WHERE articleID = 3')->fetch();
        $this->assertEquals('4', $result['number']);
    }

    public function invokeMethod(&$object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}
