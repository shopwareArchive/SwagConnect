<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Shopware\CustomModels\Connect\Attribute;
use ShopwarePlugins\Connect\Services\ExportAssignmentService;
use ShopwarePlugins\Connect\Components\ErrorHandler;
use ShopwarePlugins\Connect\Tests\ConnectTestHelperTrait;
use ShopwarePlugins\Connect\Tests\DatabaseTestCaseTrait;

class ExportAssignmentServiceTest extends PHPUnit_Framework_TestCase
{
    use DatabaseTestCaseTrait;
    use ConnectTestHelperTrait;


    /**
     * @var \Shopware\Components\Model\ModelManager
     */
    private $manager;
    /**
     * @var \ShopwarePlugins\Connect\Components\ConnectExport
     */
    private $connectExport;
    /**
     * @var ExportAssignmentService
     */
    private $exportAssignmentService;

    public function setUp()
    {
        $this->manager = Shopware()->Models();
        $this->connectExport = new \ShopwarePlugins\Connect\Components\ConnectExport(
            $this->getHelper(),
            $this->getSDK(),
            $this->manager,
            new \ShopwarePlugins\Connect\Components\Validator\ProductAttributesValidator\ProductsAttributesValidator(),
            \ShopwarePlugins\Connect\Components\ConfigFactory::getConfigInstance(),
            new ErrorHandler(),
            Shopware()->Container()->get('events')
        );

        $this->exportAssignmentService = new ExportAssignmentService(
            $this->manager->getRepository(Attribute::class),
            $this->connectExport
        );
    }

    public function test_product_export_as_batches()
    {
        $this->manager->getConnection()->executeQuery('DELETE FROM sw_connect_change');

        $this->importFixtures(__DIR__ . '/_fixtures/config_fixes.sql');
        $this->importFixtures(__DIR__ . '/_fixtures/connect_items.sql');

        $this->exportAssignmentService->exportBatchOfAllProducts(0, 1);
        $this->exportAssignmentService->exportBatchOfAllProducts(2, 1);

        $expectedChanges = [
            [
                'c_entity_id' => '2',
                'c_operation' => 'update'
            ],
            [
                'c_entity_id' => '2-124',
                'c_operation' => 'update'
            ]
        ];

        $changes = $this->manager->getConnection()->fetchAll('SELECT c_entity_id, c_operation FROM sw_connect_change ');
        $this->assertEquals($expectedChanges, $changes);

        $timestampAndRevision = $this->manager->getConnection()->fetchAll('SELECT c_revision, changed FROM sw_connect_change');

        //assert that time stamp and revision exist and are correctly formatted
        $this->assertCount(2, $timestampAndRevision);

        foreach ($timestampAndRevision as $item) {
            $this->assertRegExp("/\d*\.\d*$/", $item['c_revision']);
            $this->assertRegExp("/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/", $item['changed']);
        }
    }
}
