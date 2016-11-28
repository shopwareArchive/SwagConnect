<?php

use Tests\ShopwarePlugins\Connect\ConnectTestHelper;
use ShopwarePlugins\Connect\Services\PaymentService;
use Shopware\CustomModels\Connect\PaymentRepository as CustomPaymentRepository;
use Shopware\Models\Payment\Repository as PaymentRepository;
use Shopware\Models\Payment\Payment;
use Shopware\Models\Attribute\Payment as AttrPayment;

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
            array(
                'name' => 'dummy',
                'description' => 'Dummy',
                'additionaldescription' => 'Dummy',
                'template' => 'Dummy',
            )
        );

        $this->paymentId = $this->db->lastInsertId();

        $this->db->insert(
            's_core_paymentmeans_attributes',
            array(
                'paymentmeanID' => $this->paymentId,
                'connect_is_allowed' => 1,
            )
        );
    }

    public function testConnectIsAllow()
    {
        $this->paymentService->updateConnectAllowed($this->paymentId, false);

        $sql = 'SELECT connect_is_allowed as connectIsAllowed FROM s_core_paymentmeans_attributes WHERE paymentmeanID = ?';
        $result = $this->db->fetchRow($sql, array($this->paymentId));

        $this->assertEquals(0, $result['connectIsAllowed']);
    }

    public function tearDown()
    {
        $this->db->delete('s_core_paymentmeans', array('id = ?' => $this->paymentId));
    }
}