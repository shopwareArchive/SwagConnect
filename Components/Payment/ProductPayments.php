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

namespace Shopware\Bepado\Components\Payment;

use Bepado\SDK\Struct\PaymentStatus;
use Shopware\Bepado\Components\Logger;
use Shopware\Bepado\Components\OrderQuery\RemoteOrderQuery;

/**
 * Class ProductPayments
 */
class ProductPayments implements \Bepado\SDK\ProductPayments
{
    /**
     * @var \Shopware\Bepado\Components\OrderQuery\RemoteOrderQuery
     */
    private $remoteOrderQuery;

    private $paymentStatusRepository;

    private $db;

    /**
     * @var \Shopware\Bepado\Components\Logger
     */
    private $logger;

    public function __construct(RemoteOrderQuery $orderQuery)
    {
        $this->remoteOrderQuery = $orderQuery;
    }

    /**
     * Return last revision
     *
     * @return string
     */
    public function lastRevision()
    {
        $query = $this->getDb()->query(
            'SELECT
                `d_value`
            FROM
                `bepado_data`
            WHERE
                `d_key` = "payment_revision"'
        );

        $paymentRevision = $query->fetchColumn();
        if (!$paymentRevision) {
            return 0;
        }

        return $paymentRevision;
    }

    /**
     * Find order and update payment status
     *
     * @param int $localOrderId
     * @param PaymentStatus $paymentStatus
     * @return mixed|void
     */
    public function updatePaymentStatus($localOrderId, PaymentStatus $paymentStatus)
    {
        $order = $this->remoteOrderQuery->getBepadoOrder($localOrderId);
        if (!$order) {
            $this->getLogger()->write(
                true,
                sprintf(
                    'Order with id "%s" not found',
                    $localOrderId
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
                    $localOrderId
                )
            );
            return;
        }

        $order->setPaymentStatus($orderPaymentStatus);

        Shopware()->Models()->persist($order);
        Shopware()->Models()->flush();

        $this->updatePaymentRevision();

        return true;
    }

    /**
     * Map bepado payment status to plugin payment status
     *
     * @param $status
     * @return string
     */
    private function mapPaymentStatus($status)
    {
        return 'bepado ' . $status;
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

    /**
     * Update payment revision in DB
     */
    private function updatePaymentRevision()
    {
        $query = Shopware()->Db()->prepare(
            'INSERT INTO
                bepado_data (
                    `d_key`,
                    `d_value`
                )
            VALUES (
                "payment_revision",
                ?
            )
            ON DUPLICATE KEY UPDATE
                `d_value` = VALUES(`d_value`)
            ;'
        );
        $query->execute(array((string)microtime(true)));
    }
} 