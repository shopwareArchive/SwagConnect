<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Tests\Unit\Components;

use Shopware\CustomModels\Connect\AttributeRepository;
use ShopwarePlugins\Connect\Components\CategoryExtractor;
use ShopwarePlugins\Connect\Components\CategoryResolver\DefaultCategoryResolver;
use ShopwarePlugins\Connect\Components\RandomStringGenerator;
use ShopwarePlugins\Connect\Tests\AbstractConnectUnitTest;
use ShopwarePlugins\Connect\Tests\UnitTestCaseTrait;
use Shopware\Connect\Gateway;
use Enlight_Components_Db_Adapter_Pdo_Mysql as Pdo;

class CategoryExtractorTest extends AbstractConnectUnitTest
{
    use UnitTestCaseTrait;

    /**
     * @var CategoryExtractor
     */
    private $categoryExtractor;

    /**
     * @before
     */
    public function prepareMocks()
    {
        $this->categoryExtractor = new CategoryExtractor(
            $this->createMock(AttributeRepository::class),
            $this->createMock(DefaultCategoryResolver::class),
            $this->createMock(Gateway::class),
            $this->createMock(RandomStringGenerator::class),
            $this->createMock(Pdo::class)
        );
    }

    public function testExtractValidNode()
    {
        list($shopId, $stream) = $this->categoryExtractor->extractNode('shopId5~stream~AwesomeProducts~/english/boots/nike');

        self::assertEquals(5, $shopId);
        self::assertEquals('AwesomeProducts', $stream);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testExtractInvalidNodeThrowsException()
    {
        $this->categoryExtractor->extractNode('shopId5~/english/boots/nike');
    }
}
