<?php

namespace Shopware\Bepado\Subscribers;
use Shopware\Bepado\Components\Utils;

/**
 * Handles article lifecycle events in order to automatically update/delete products to/from bepado
 *
 * Class Lifecycle
 * @package Shopware\Bepado\Subscribers
 */
class Lifecycle extends BaseSubscriber
{

    public function getSubscribedEvents()
    {
        return array(
            'Shopware\Models\Article\Article::postPersist' => 'onUpdateArticle',
            'Shopware\Models\Article\Article::postUpdate' => 'onUpdateArticle',
            'Shopware\Models\Article\Detail::postUpdate' => 'onUpdateArticle',
            'Shopware\Models\Article\Article::preRemove' => 'onDeleteArticle',
            'Shopware\Models\Order\Order::postUpdate' => 'onUpdateOrder',
        );
    }

    /**
     * @param \Enlight_Event_EventArgs $eventArgs
     */
    public function onUpdateOrder(\Enlight_Event_EventArgs $eventArgs)
    {
        /** @var \Shopware\Models\Order\Order $order */
        $order = $eventArgs->get('entity');

        $attribute = $order->getAttribute();
        if (!$attribute || !$attribute->getBepadoShopId()) {
            return;
        }

        // Compute the changeset and return, if orderStatus did not change
        $changeSet = $eventArgs->get('entityManager')->getUnitOfWork()->getEntityChangeSet($order);
        if (!isset($changeSet['orderStatus'])) {
            return;
        }


        $orderUtil = new Utils\OrderStatus();
        $orderStatus = $orderUtil->getOrderStatusStructFromOrder($order);

        try {
            $this->getSDK()->updateOrderStatus($orderStatus);
        } catch (\Exception $e) {
            // if sn is not available, proceed without exception
        }
    }

    /**
     * Callback function to delete an product from bepado after it is going to be deleted locally
     *
     * @param \Enlight_Event_EventArgs $eventArgs
     */
    public function onDeleteArticle(\Enlight_Event_EventArgs $eventArgs)
    {
        $entity = $eventArgs->get('entity');
        $id = $entity->getId();

        $sdk = $this->getSDK();
        $sdk->recordDelete($id);
    }

    /**
     * Callback method to update changed bepado products
     *
     * @param \Enlight_Event_EventArgs $eventArgs
     */
    public function onUpdateArticle(\Enlight_Event_EventArgs $eventArgs)
    {
        if (!$this->Config()->get('autoUpdateProducts', true)) {
            return;
        }

        $entity = $eventArgs->get('entity');

        try {
            if ($entity instanceof \Shopware\Models\Article\Detail) {
                $entity = $entity->getArticle();
            }
        } catch(\Exception $e) {
            return;
        }


        $id = $entity->getId();

        $model = $this->getHelper()->getArticleModelById($id);

        // Check if we have a valid model
        if (!$model) {
            return;
        }

        // Check if entity is a bepado product
        $attribute = $this->getHelper()->getBepadoAttributeByModel($model);
        if (!$attribute) {
            return;
        }
        $status = $attribute->getExportStatus();
        $shopId = $attribute->getShopId();
        if (empty($status) || !empty($shopId)) {
            return;
        }

        // Mark the product for bepado update
        try {
            $this->getHelper()->insertOrUpdateProduct(
                array($id), $this->getSDK()
            );

        } catch (\Exception $e) {
            // If the update fails due to missing requirements
            // (e.g. category assignment), continue without error
        }

    }
}