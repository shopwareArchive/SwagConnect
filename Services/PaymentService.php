<?php

namespace ShopwarePlugins\Connect\Services;

use Shopware\Models\Payment\Repository as PaymentRepository;
use Shopware\CustomModels\Connect\PaymentRepository as ConnectPaymentRepository;

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
     * @param int $paymentId
     * @param int $connectIsAllowed
     */
    public function updateConnectAllowed($paymentId, $connectIsAllowed)
    {
        $this->connectPaymentRepository->updateConnectIsAllowed($paymentId, $connectIsAllowed);
    }
}