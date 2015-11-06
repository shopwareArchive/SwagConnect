<?php
/**
 * Shopware 4.0
 * Copyright © 2013 shopware AG
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 */

namespace ShopwarePlugins\Connect\Components\Payment;

use Shopware\Connect\Struct\PaymentStatus;
use ShopwarePlugins\Connect\Components\Logger;
use ShopwarePlugins\Connect\Components\OrderQuery\RemoteOrderQuery;

/**
 * Class ProductPayments
 */
class ProductPayments implements \Shopware\Connect\ProductPayments
{
    private $paymentStatusRepository;

    private $db;

    /**
     * @var \ShopwarePlugins\Connect\Components\Logger
     */
    private $logger;

    /**
     * Find order and update payment status
     *
     * @param PaymentStatus $paymentStatus
     * @return mixed|void
     */
    public function updatePaymentStatus(PaymentStatus $paymentStatus)
    {
        // $paymentStatus->localOrderId is actually ordernumber for this shop
        // e.g. BP-35-20002
        $repository = Shopware()->Models()->getRepository('Shopware\Models\Order\Order');
        $order = $repository->findOneBy(array('number' => $paymentStatus->localOrderId));

        if (!$order) {
            $this->getLogger()->write(
                true,
                sprintf(
                    'Order with id "%s" not found',
                    $paymentStatus->localOrderId
                ),
                serialize($paymentStatus)
            );
            return;
        }

        /** @var \Shopware\Models\Order\Status $orderPaymentStatus */
        $orderPaymentStatus = $this->getPaymentStatusRepository()->findOneBy(
            array('description' => $this->mapPaymentStatus($paymentStatus->paymentStatus))
        );

        if (!$orderPaymentStatus) {
            $this->getLogger()->write(
                true,
                sprintf(
                    'Payment status "%s" not found',
                    $paymentStatus->paymentStatus
                ),
                sprintf(
                    'Order with id "%s"',
                    $paymentStatus->localOrderId
                )
            );
            return;
        }

        $order->setPaymentStatus($orderPaymentStatus);

        Shopware()->Models()->persist($order);
        Shopware()->Models()->flush();

        return true;

    }

    /**
     * Map connect payment status to plugin payment status
     *
     * @param $status
     * @return string
     */
    private function mapPaymentStatus($status)
    {
        return 'connect ' . $status;
    }

    /**
     * Helper method
     * Return instance of Status repository
     *
     * @return \Shopware\Components\Model\ModelRepository
     */
    private function getPaymentStatusRepository()
    {
        if (!$this->paymentStatusRepository) {
            $this->paymentStatusRepository = Shopware()->Models()->getRepository('Shopware\Models\Order\Status');
        }

        return $this->paymentStatusRepository;
    }

    /**
     * Helper method
     * Return instance of DB class
     *
     * @return \Enlight_Components_Db_Adapter_Pdo_Mysql
     */
    private function getDb()
    {
        if (!$this->db) {
            $this->db = Shopware()->Db();
        }

        return $this->db;
    }

    /**
     * Helper method
     * Return instance of Logger class
     *
     * @return Logger
     */
    private function getLogger()
    {
        if (!$this->logger) {
            $this->logger = new Logger($this->getDb());
        }

        return $this->logger;
    }
} 