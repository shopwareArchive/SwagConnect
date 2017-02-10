<?php

namespace ShopwarePlugins\Connect\Components\Utils;

use Shopware\Connect\Struct\Message;
use Shopware\Connect\Struct\OrderStatus as OrderStatusStruct;
use Shopware\Models\Order\Order;

/**
 * Class OrderStatusMapper
 * @package ShopwarePlugins\Connect\Components\Utils
 */
class OrderStatusMapper
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
                '0' => OrderStatusStruct::STATE_OPEN, // 0 = open
                '1' => OrderStatusStruct::STATE_IN_PROCESS, // 1 = in progress (waiting)
                '3' => OrderStatusStruct::STATE_IN_PROCESS, // 3 = partially completed
                '5' => OrderStatusStruct::STATE_IN_PROCESS, // 5 = ready for shipping
                '6' => OrderStatusStruct::STATE_IN_PROCESS, // 6 = partially delivered
                '8' => OrderStatusStruct::STATE_IN_PROCESS, // 8 = Clearance needed
                '2' => OrderStatusStruct::STATE_DELIVERED, // 2 = Completely done
                '7' => OrderStatusStruct::STATE_DELIVERED, // 7 = Completely delivered
                '-1' => OrderStatusStruct::STATE_CANCELED, // -1 = Canceled
                '4' => OrderStatusStruct::STATE_ERROR, // 4 = Storno / Rejected
            );

            $this->mapping = Shopware()->Events()->filter('Connect_OrderStatus_Mapping', $this->mapping);
        }

        return $this->mapping;
    }

    /**
     * Helper to map shopware order states to connect order states
     *
     * @param $swOrderStatus
     * @return string
     */
    public function mapShopwareOrderStatusToConnect($swOrderStatus)
    {
        $swOrderStatus = (string) $swOrderStatus;

        $mapping = $this->getMapping();

        if (!isset($mapping[$swOrderStatus])) {
            return OrderStatusStruct::STATE_OPEN;
        }

        return $mapping[$swOrderStatus];
    }

    /**
     * @param Order $order
     * @return OrderStatusStruct
     */
    public function getOrderStatusStructFromOrder(Order $order)
    {
        $message = Shopware()->Snippets()->getNamespace('backend/static/payment_status')->get(
            $order->getOrderStatus()->getName()
        );

        return new OrderStatusStruct(array(
            'id' => (string) $order->getNumber(),
            'status' => $this->mapShopwareOrderStatusToConnect($order->getOrderStatus()->getId()),
            'messages' => array(
                new Message(['message' => $message])
            )
        ));
    }

}