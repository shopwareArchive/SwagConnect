<?php

namespace ShopwarePlugins\Connect\Tests\Unit\Components;

use Shopware\CustomModels\Connect\Attribute;
use ShopwarePlugins\Connect\Components\ProductStream\ProductStreamsAssignments;
use ShopwarePlugins\Connect\Components\Variant\StatefulVariantRegenerator;
use ShopwarePlugins\Connect\Tests\AbstractConnectUnitTest;
use ShopwarePlugins\Connect\Components\ConnectExport;
use ShopwarePlugins\Connect\Components\ProductStream\ProductStreamService;
use ShopwarePlugins\Connect\Components\Config;
use ShopwarePlugins\Connect\Tests\PhpUnitTestCasePolyfillTrait;

class StatefulVariantRegeneratorTest extends AbstractConnectUnitTest
{
    use PhpUnitTestCasePolyfillTrait;

    /**
     * @var StatefulVariantRegenerator
     */
    private $variantRegenerator;

    private $connectExport;

    private $productStreamService;

    /**
     * @before
     */
    public function prepareMocks()
    {
        $this->connectExport = $this->createMock(ConnectExport::class);
        $this->productStreamService = $this->createMock(ProductStreamService::class);

        $this->variantRegenerator = new StatefulVariantRegenerator(
            $this->connectExport,
            $this->productStreamService,
            Config::UPDATE_AUTO
        );
    }

    public function testGenerateChanges()
    {
        $articleId = 6;

        $this->variantRegenerator->setInitialSourceIds($articleId, ['6-1', '6-2', '6-3']);
        $this->variantRegenerator->setCurrentSourceIds($articleId, ['6-1']);

        $this->connectExport->expects($this->at(0))
            ->method('recordDelete')
            ->with('6-2');
        $this->connectExport->expects($this->at(1))
            ->method('recordDelete')
            ->with('6-3');

        $this->connectExport->method('updateConnectItemsStatus')
            ->with([1 => '6-2', 2 => '6-3'], Attribute::STATUS_DELETE);

        $streamId = 3;
        $this->connectExport->method('export')
            ->with([$articleId => ['6-1']], $this->captureArg($streamAssignments));
        $this->productStreamService->method('collectRelatedStreamsAssignments')
            ->with([$articleId])
            ->willReturn([$articleId => [$streamId => 'Shoes Stream']]);

        $this->variantRegenerator->generateChanges(6);

        $this->assertInstanceOf(ProductStreamsAssignments::class, $streamAssignments);
        $this->assertEquals([$articleId => [$streamId => 'Shoes Stream']], $streamAssignments->assignments);
    }

    public function testGenerateWithActiveCron()
    {
        $connectExport = $this->createMock(ConnectExport::class);
        $productStreamService = $this->createMock(ProductStreamService::class);

        $variantRegenerator = new StatefulVariantRegenerator(
            $connectExport,
            $productStreamService,
            Config::UPDATE_CRON_JOB
        );

        $articleId = 5;
        $variantRegenerator->setInitialSourceIds($articleId, ['5-1', '5-2', '5-3']);
        $variantRegenerator->setCurrentSourceIds($articleId, ['5-1']);

        $connectExport->expects($this->at(0))
            ->method('recordDelete')
            ->with('5-2');
        $connectExport->expects($this->at(1))
            ->method('recordDelete')
            ->with('5-3');

        $connectExport->method('updateConnectItemsStatus')
            ->with([1 => '5-2', 2 => '5-3'], Attribute::STATUS_DELETE);

        $connectExport->method('markArticleForCronUpdate')
            ->with($articleId);

        $connectExport->expects($this->never())
            ->method('export')
            ->with($this->anything());

        $variantRegenerator->generateChanges(5);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Initial sourceIds are not set!
     */
    public function testMissingInitialSourceIds()
    {
        $this->variantRegenerator->generateChanges(7);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Current sourceIds are not set!
     */
    public function testMissingCurrentSourceIds()
    {
        $this->variantRegenerator->setInitialSourceIds(8, ['5-1', '5-2', '5-3']);
        $this->variantRegenerator->generateChanges(8);
    }
}
