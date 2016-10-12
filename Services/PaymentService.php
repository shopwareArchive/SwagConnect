<?php

namespace ShopwarePlugins\Connect\Services;

use Shopware\Models\Payment\Repository as PaymentRepository;
use Shopware\CustomModels\Connect\PaymentRepository as CustomPaymentRepository;

class PaymentService
{
    private $paymentRepository;
    private $customPaymentRepository;

    public function __construct(
        PaymentRepository $paymentRepository,
        CustomPaymentRepository $customPaymentRepository
    ) {
        $this->paymentRepository = $paymentRepository;
        $this->customPaymentRepository = $customPaymentRepository;
    }

    /**
     * @param $payments
     * @return array
     */
    public function allowConnect($payments)
    {
        $connectSuppliers = $this->customPaymentRepository->getConnectAllowedPayments();

        foreach ($payments as $index => $supplier) {
            $payments[$index]['connectIsAllowed'] = in_array($supplier['id'], $connectSuppliers);
        }

        return $payments;
    }

    /**
     * @param $paymentId
     * @param $connectIsAllowed
     */
    public function updateConnectAllowed($paymentId, $connectIsAllowed)
    {
        $this->customPaymentRepository->updateConnectIsAllowed($paymentId, $connectIsAllowed);
    }
}