<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Tests\Integration\Subscribers;

use ShopwarePlugins\Connect\Components\Config;
use ShopwarePlugins\Connect\Components\ProductStream\ProductSearch;
use ShopwarePlugins\Connect\Subscribers\ServiceContainer;
use ShopwarePlugins\Connect\Tests\ConnectTestHelperTrait;
use ShopwarePlugins\Connect\Tests\DatabaseTestCaseTrait;

class ServiceContainerTest extends \PHPUnit_Framework_TestCase
{
    use DatabaseTestCaseTrait;
    use ConnectTestHelperTrait;

    /**
     * @var ServiceContainer
     */
    private $serviceContainer;

    private $config;

    public function setUp()
    {
        $this->config = $this->createMock(Config::class);
        $this->serviceContainer = new ServiceContainer(
            Shopware()->Models(),
            Shopware()->Db(),
            Shopware()->Container(),
            $this->config
        );
    }

    public function testOnProductSearch()
    {
        $this->assertInstanceOf(ProductSearch::class, $this->serviceContainer->onProductSearch());
    }
}
