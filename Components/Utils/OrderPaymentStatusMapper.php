<?php

namespace ShopwarePlugins\Connect\Components\Utils;

use Shopware\Connect\Struct\PaymentStatus;
use Shopware\Models\Order\Order;

/**
 * Class OrderPaymentStatusMapper
 * @package ShopwarePlugins\Connect\Components\Utils
 */
class OrderPaymentStatusMapper
{

    private $mapping;

    /**
     * Returns an array of mappings from sw order states to connect order states
     *
     * Can be modified and extended by using the Connect_OrderStatus_Mapping filter event
     *
     * @return mixed
     */
    protected function getMapping()
    {
        if (!$this->mapping) {
            $this->mapping = array(
                'open' => PaymentStatus::PAYMENT_OPEN,
                'completely_paid' => PaymentStatus::PAYMENT_RECEIVED,
                'sc_requested' => PaymentStatus::PAYMENT_REQUESTED,
                'sc_initiated' => PaymentStatus::PAYMENT_INITIATED,
                'sc_instructed' => PaymentStatus::PAYMENT_INSTRUCTED,
                'sc_verify' => PaymentStatus::PAYMENT_VERIFY,
                'sc_aborted' => PaymentStatus::PAYMENT_ABORTED,
                'sc_timeout' => PaymentStatus::PAYMENT_TIMEOUT,
                'sc_pending' => PaymentStatus::PAYMENT_PENDING,
                'sc_received' => PaymentStatus::PAYMENT_RECEIVED,
                'sc_refunded' => PaymentStatus::PAYMENT_REFUNDED,
                'sc_loss' => PaymentStatus::PAYMENT_LOSS,
                'sc_error' => PaymentStatus::PAYMENT_ERROR,
            );

            $this->mapping = Shopware()->Events()->filter('Connect_OrderPaymentStatus_Mapping', $this->mapping);
        }

        return $this->mapping;
    }

    /**
     * Helper to map shopware order states to connect order states
     *
     * @param $swOrderPaymentStatus
     * @return string
     */
    public function mapShopwareOrderPaymentStatusToConnect($swOrderPaymentStatus)
    {
        $swOrderPaymentStatus = (string) $swOrderPaymentStatus;

        $mapping = $this->getMapping();

        if (!array_key_exists($swOrderPaymentStatus, $mapping)) {
            return PaymentStatus::PAYMENT_OPEN;
        }

        return $mapping[$swOrderPaymentStatus];
    }

    /**
     * @param Order $order
     * @return PaymentStatus
     */
    public function getPaymentStatus(Order $order)
    {
        $paymentStatus = $this->mapShopwareOrderPaymentStatusToConnect(
            $order->getPaymentStatus()->getName()
        );

        return new PaymentStatus(array(
            'localOrderId' => $order->getNumber(),
            'paymentStatus' => $paymentStatus,
            'paymentProvider' => $order->getPayment()->getName(),
            'providerTransactionId' => $order->getTransactionId(),
        ));
    }

}