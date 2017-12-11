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

class RemoteToLocalCategoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RemoteToLocalCategory
     */
    private $remoteToLocalCategory;

    public function setUp()
    {
        $this->remoteToLocalCategory = new RemoteToLocalCategory();
    }

    public function testId()
    {
        $this->remoteToLocalCategory->setId(1);

        $this->assertEquals(1, $this->remoteToLocalCategory->getId());
    }

    public function testStream()
    {
        $this->remoteToLocalCategory->setStream('Awesome Stream');

        $this->assertEquals('Awesome Stream', $this->remoteToLocalCategory->getStream());
    }

    public function testRemoteCategory()
    {
        $category = new RemoteCategory();
        $category->setLabel('Test-Category');
        $category->setShopId(1234);
        $category->setCategoryKey('/deutsch/test_category');
        $this->remoteToLocalCategory->setRemoteCategory($category);

        $this->assertEquals($category, $this->remoteToLocalCategory->getRemoteCategory());
    }

    public function testLocalCategory()
    {
        $category = new Category();
        $category->setPrimaryIdentifier(1234);
        $category->setName('Test-Category');
        $this->remoteToLocalCategory->setLocalCategory($category);

        $this->assertEquals($category, $this->remoteToLocalCategory->getLocalCategory());
    }
}
