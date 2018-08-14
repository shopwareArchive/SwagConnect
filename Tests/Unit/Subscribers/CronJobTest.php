<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Tests\Unit\Subscribers;

use Shopware\Bundle\SearchBundle\Criteria;
use Shopware\Bundle\SearchBundle\ProductSearchResult;
use ShopwarePlugins\Connect\Components\Config;
use ShopwarePlugins\Connect\Components\ConnectExport;
use ShopwarePlugins\Connect\Components\ConnectFactory;
use ShopwarePlugins\Connect\Components\Helper;
use ShopwarePlugins\Connect\Components\ProductStream\ProductSearch;
use ShopwarePlugins\Connect\Components\ProductStream\ProductStreamsAssignments;
use ShopwarePlugins\Connect\Components\ProductStream\ProductStreamService;
use ShopwarePlugins\Connect\Subscribers\CronJob;
use Shopware\Components\DependencyInjection\Container;
use Shopware\Models\ProductStream\ProductStream;
use Shopware\Bundle\StoreFrontBundle\Struct\ListProduct;

class CronJobTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CronJob
     */
    private $cronJob;

    /**
     * @var Container
     */
    private $container;

    private $streamService;

    private $productSearch;

    private $helper;

    private $connectExport;

    /**
     * @before
     */
    public function prepareMocks()
    {
        $this->container = $this->createMock(Container::class);
        $this->streamService = $this->createMock(ProductStreamService::class);
        $this->productSearch = $this->createMock(ProductSearch::class);
        $this->helper = $this->createMock(Helper::class);
        $this->connectExport = $this->createMock(ConnectExport::class);

        $this->container
            ->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap([
                ['swagconnect.product_stream_service', Container::EXCEPTION_ON_INVALID_REFERENCE, $this->streamService],
                ['swagconnect.product_search', Container::EXCEPTION_ON_INVALID_REFERENCE, $this->productSearch]
            ]));

        $this->cronJob = new CronJob(
            (new ConnectFactory())->getSDK(),
            $this->connectExport,
            $this->createMock(Config::class),
            $this->helper,
            $this->container,
            $this->streamService
        );
    }

    public function test_subscribed_events()
    {
        $this->assertSame(
            [
                'Shopware_CronJob_ShopwareConnectImportImages' => 'importImages',
                'Shopware_CronJob_ShopwareConnectUpdateProducts' => 'updateProducts',
                'Shopware_CronJob_ConnectExportDynamicStreams' => 'exportDynamicStreams',
            ],
            CronJob::getSubscribedEvents()
        );
    }

    public function test_without_exported_dynamic_stream()
    {
        $this->streamService->expects($this->once())
            ->method('getAllExportedStreams')
            ->with(ProductStreamService::DYNAMIC_STREAM)
            ->willReturn([]);

        $this->cronJob->exportDynamicStreams(new \Shopware_Components_Cron_CronJob());
    }

    public function test_export_dynamic_stream()
    {
        $ps = new ProductSearchResult(
            [
                'SW100001' => new ListProduct(14, 26, 'SW100001'),
                'SW100003' => new ListProduct(15, 35, 'SW100002'),
            ],
            2,
            [],
            new Criteria(),
            Shopware()->Container()->get('shopware_storefront.context_service')->getShopContext()
        );

        $stream = $this->createMock(ProductStream::class);
        $stream->expects($this->once())
            ->method('getId')
            ->willReturn(5);

        $this->productSearch->expects($this->once())
            ->method('getProductFromConditionStream')
            ->with($stream)
            ->willReturn($ps);

        $this->helper->expects($this->once())
            ->method('getArticleIdsByNumber')
            ->with(['SW100001', 'SW100003'])
            ->willReturn([14, 15]);

        $this->helper->expects($this->once())
            ->method('getArticleSourceIds')
            ->with([14, 15])
            ->willReturn([14, 15]);

        $this->streamService->expects($this->once())
            ->method('getAllExportedStreams')
            ->with(ProductStreamService::DYNAMIC_STREAM)
            ->willReturn([
                $stream
            ]);

        $this->streamService->expects($this->once())
            ->method('createStreamRelation')
            ->with(5, [14, 15]);

        $assignment = [
            14 => [
                5 => 'Shoes Stream'
            ],
            15 => [
                5 => 'Shoes Stream'
            ]
        ];
        $streamAssignments = new ProductStreamsAssignments(
            ['assignments' => $assignment]
        );
        $this->streamService->expects($this->once())
            ->method('prepareStreamsAssignments')
            ->with(5, false)
            ->willReturn($streamAssignments);

        $this->connectExport->expects($this->once())
            ->method('export')
            ->with([14, 15], $streamAssignments)
            ->willReturn([]);

        $this->streamService->expects($this->once())
            ->method('changeStatus')
            ->with(5, ProductStreamService::STATUS_EXPORT);

        $this->cronJob->exportDynamicStreams(new \Shopware_Components_Cron_CronJob());
    }
}
