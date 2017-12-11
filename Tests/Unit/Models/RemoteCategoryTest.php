<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Tests\Unit\Components;

use Shopware\CustomModels\Connect\RemoteCategory;
use Shopware\CustomModels\Connect\RemoteToLocalCategory;
use Shopware\Models\Category\Category;

class RemoteCategoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RemoteCategory
     */
    private $remoteCategory;

    public function setUp()
    {
        $this->remoteCategory = new RemoteCategory();
    }

    public function testCategoryKey()
    {
        $this->remoteCategory->setCategoryKey('/deutsch/test');

        $this->assertEquals('/deutsch/test', $this->remoteCategory->getCategoryKey());
    }

    public function testLabel()
    {
        $this->remoteCategory->setLabel('Test');

        $this->assertEquals('Test', $this->remoteCategory->getLabel());
    }

    public function testShopId()
    {
        $this->remoteCategory->setShopId(1234);

        $this->assertEquals(1234, $this->remoteCategory->getShopId());
    }

    public function testRemoteToLocalCategories()
    {
        $category1 = new RemoteToLocalCategory();
        $category1->setStream('test stream');
        $category1->setId(1);
        $category2 = new RemoteToLocalCategory();
        $category2->setStream('test');
        $category2->setId(2);
        $remoteToLocalCategories = [
            $category1,
            $category2
        ];
        $this->remoteCategory->setRemoteToLocalCategories($remoteToLocalCategories);

        $this->assertEquals($remoteToLocalCategories, $this->remoteCategory->getRemoteToLocalCategories());
    }
}
