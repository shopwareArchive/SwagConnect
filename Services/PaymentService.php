<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Services;

use Shopware\CustomModels\Connect\PaymentRepository as ConnectPaymentRepository;
use Shopware\Models\Payment\Repository as PaymentRepository;

class PaymentService
{
    private $paymentRepository;
    private $connectPaymentRepository;

    public function __construct(
        PaymentRepository $paymentRepository,
        ConnectPaymentRepository $connectPaymentRepository
    ) {
        $this->paymentRepository = $paymentRepository;
        $this->connectPaymentRepository = $connectPaymentRepository;
    }

    /**
     * @param array $payments
     *
     * @return array
     */
    public function allowConnect(array $payments)
    {
        $connectSuppliers = $this->connectPaymentRepository->getConnectAllowedPayments();

        foreach ($payments as $index => $payment) {
            $payments[$index]['connectIsAllowed'] = in_array($payment['id'], $connectSuppliers);
        }

        return $payments;
    }

    /**
     * @param int  $paymentId
     * @param bool $connectIsAllowed
     */
    public function updateConnectAllowed($paymentId, $connectIsAllowed)
    {
        $this->connectPaymentRepository->updateConnectIsAllowed($paymentId, $connectIsAllowed);
    }
}
