<?php

namespace ShopwarePlugins\Connect\Subscribers;

use ShopwarePlugins\Connect\Components\Utils\ConnectOrderUtil;

class OrderDocument extends BaseSubscriber
{
    public function getSubscribedEvents()
    {
        return array(
            'Shopware_Models_Document_Order::processOrder::before' => 'makeShippingTaxRateAvailable'
        );
    }

    /**
     * Make sure, that connect orders with "unknown" tax rates for shipping are handled properly
     *
     * @param \Enlight_Hook_HookArgs $args
     */
    public function makeShippingTaxRateAvailable(\Enlight_Hook_HookArgs $args)
    {
        $orderModel = $args->getSubject();
        $order = $orderModel->order;

        // This will apply for remote orders
        if ($order->attributes && $order->attributes['connect_order_id']) {
            $this->setDefaultTaxRate($order);
        }

        // This will apply for local connect orders
        $orderUtil = new ConnectOrderUtil();
        if ($orderUtil->hasLocalOrderConnectProducts($order->id)) {
            $this->setDefaultTaxRate($order);
        }


    }

    /**
     * This will temporarily set the default shipping tax rate to the current shipping tax rate
     * This will prevent shopware from setting "tax rate: 0" for unknown tax rates.
     *
     * The sTAXSHIPPING seems not te be used by default any more and this call will only write
     * the tax rate for the current dispatch, so there should be no side effects
     *
     * @param $order
     */
    public function setDefaultTaxRate($order)
    {
        $taxRate = round(($order->invoice_shipping / $order->invoice_shipping_net) * 100 - 100, 2);

        Shopware()->Config()->sTAXSHIPPING = $taxRate;
    }
}
