<?php

namespace Shopware\Bepado\Subscribers;

class OrderDocument extends BaseSubscriber
{
    public function getSubscribedEvents()
    {
        return array(
            'Shopware_Models_Document_Order::processOrder::before' => 'makeShippingTaxRateAvailable'
        );
    }

    public function makeShippingTaxRateAvailable(\Enlight_Hook_HookArgs $args)
    {
        $orderModel = $args->getSubject();
        $order = $orderModel->order;

        // If this is not a bepado order, return
        if (!$order->attributes || !$order->attributes['bepado_order_id']) {
            return;
        }


        $taxRate = round(($order->invoice_shipping / $order->invoice_shipping_net) * 100 - 100, 2);

        /**
         * This will temporarily set the default shipping tax rate to the current shipping tax rate
         * This will prevent shopware from setting "tax rate: 0" for unknown tax rates.
         *
         * The sTAXSHIPPING seems not te be used by default any more and this call will only write
         * the tax rate for the current dispatch, so there should be no side effects
         */
        Shopware()->Config()->sTAXSHIPPING = $taxRate;
    }
}
