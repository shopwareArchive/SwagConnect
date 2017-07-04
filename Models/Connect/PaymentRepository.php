<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\CustomModels\Connect;

use Shopware\Components\Model\ModelManager;

class PaymentRepository
{
    /**
     * @var ModelManager
     */
    private $modelManager;

    /**
     * @var \Doctrine\DBAL\Connection
     */
    private $connection;

    /**
     * PaymentRepository constructor.
     * @param ModelManager $modelManager
     */
    public function __construct(ModelManager $modelManager)
    {
        $this->modelManager = $modelManager;
        $this->connection = $modelManager->getConnection();
    }

    /**
     * @return array
     */
    public function getConnectAllowedPayments()
    {
        /** @var \Doctrine\DBAL\Connection $conn */
        $builder = $this->connection->createQueryBuilder();
        $builder->select('paymentmeanID')
            ->from('s_core_paymentmeans_attributes', 'pa')
            ->where('pa.connect_is_allowed = 1');

        return array_map(function ($item) {
            return $item['paymentmeanID'];
        }, $builder->execute()->fetchAll(\PDO::FETCH_ASSOC));
    }

    /**
     * @param int $paymentId
     * @param bool $connectIsAllowed
     */
    public function updateConnectIsAllowed($paymentId, $connectIsAllowed)
    {
        /** @var \Doctrine\DBAL\Connection $conn */
        $builder = $this->connection->createQueryBuilder();
        $builder->update('s_core_paymentmeans_attributes', 'pa')
            ->set('pa.connect_is_allowed', ':connectIsAllowed')
            ->where('pa.paymentmeanID = :paymentId')
            ->setParameter('connectIsAllowed', $connectIsAllowed)
            ->setParameter('paymentId', $paymentId);

        $builder->execute();
    }
}
