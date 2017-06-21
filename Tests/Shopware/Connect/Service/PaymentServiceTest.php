<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Shopware\CustomModels\Connect\PaymentRepository as CustomPaymentRepository;
use Shopware\Models\Payment\Repository as PaymentRepository;
use ShopwarePlugins\Connect\Services\PaymentService;
use Tests\ShopwarePlugins\Connect\ConnectTestHelper;

class PaymentServiceTest extends ConnectTestHelper
{
    public $db;

    public $manager;

    public $paymentId;

    /**
     * @var PaymentRepository
     */
    public $paymentRepo;

    /**
     * @var PaymentService
     */
    public $paymentService;

    public function setUp()
    {
        parent::setUp();

        $this->db = Shopware()->Db();
        $this->manager = Shopware()->Models();
        $this->paymentRepo = $this->manager->getRepository('Shopware\Models\Payment\Payment');
        $this->paymentService = new PaymentService(
            $this->paymentRepo,
            new CustomPaymentRepository($this->manager)
        );

        $this->insertDummyData();
    }

    private function insertDummyData()
    {
        $this->db->insert(
            's_core_paymentmeans',
            [
                'name' => 'dummy',
                'description' => 'Dummy',
                'additionaldescription' => 'Dummy',
                'template' => 'Dummy',
            ]
        );

        $this->paymentId = $this->db->lastInsertId();

        $this->db->insert(
            's_core_paymentmeans_attributes',
            [
                'paymentmeanID' => $this->paymentId,
                'connect_is_allowed' => 1,
            ]
        );
    }

    public function testConnectIsAllow()
    {
        $this->paymentService->updateConnectAllowed($this->paymentId, false);

        $sql = 'SELECT connect_is_allowed as connectIsAllowed FROM s_core_paymentmeans_attributes WHERE paymentmeanID = ?';
        $result = $this->db->fetchRow($sql, [$this->paymentId]);

        $this->assertEquals(0, $result['connectIsAllowed']);
    }

    public function tearDown()
    {
        $this->db->delete('s_core_paymentmeans', ['id = ?' => $this->paymentId]);
    }
}
