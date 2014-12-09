<?php

namespace Shopware\Bepado\Subscribers;
use Shopware\Bepado\Components\Config;
use Shopware\Bepado\Components\Utils;
use Shopware\Bepado\Components\BepadoExport;
use Shopware\Models\Article\Article;
use Shopware\Models\Article\Detail;

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
            'Shopware\Models\Article\Detail::preRemove' => 'onDeleteArticle',
            'Shopware\Models\Order\Order::postUpdate' => 'onUpdateOrder',
        );
    }

    /**
     * @return BepadoExport
     */
    public function getBepadoExport()
    {
        return new BepadoExport(
            $this->getHelper(),
            $this->getSDK(),
            Shopware()->Models()
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


        $orderStatusMapper = new Utils\OrderStatusMapper();
        $orderStatus = $orderStatusMapper->getOrderStatusStructFromOrder($order);

        try {
            $this->getSDK()->updateOrderStatus($orderStatus);
        } catch (\Exception $e) {
            // if sn is not available, proceed without exception
        }
    }

    /**
     * Callback function to delete an product or product detail
     * from bepado after it is going to be deleted locally
     *
     * @param \Enlight_Event_EventArgs $eventArgs
     */
    public function onDeleteArticle(\Enlight_Event_EventArgs $eventArgs)
    {
        $entity = $eventArgs->get('entity');

        if ($entity instanceof \Shopware\Models\Article\Article) {
            $this->getBepadoExport()->syncDeleteArticle($entity);
        } else {
            $this->getBepadoExport()->syncDeleteDetail($entity);
        }
    }

    /**
     * Callback method to update changed bepado products
     *
     * @param \Enlight_Event_EventArgs $eventArgs
     */
    public function onUpdateArticle(\Enlight_Event_EventArgs $eventArgs)
    {
        /** @var \Shopware\Bepado\Components\Config $configComponent */
        $configComponent = new Config(Shopware()->Models());

        if (!$configComponent->getConfig('autoUpdateProducts', true)) {
            return;
        }

        $entity = $eventArgs->get('entity');

        if (!$entity instanceof \Shopware\Models\Article\Article
            && !$entity instanceof \Shopware\Models\Article\Detail) {
            return;
        }

        $id = $entity->getId();
        $className = get_class($entity);
        $model = Shopware()->Models()->getRepository($className)->find($id);
        // Check if we have a valid model
        if (!$model) {
            return;
        }

        // Check if entity is a bepado product
        $attribute = $this->getHelper()->getBepadoAttributeByModel($model);
        if (!$attribute) {
            return;
        }

        // todo@dn: Check logic
        $status = $attribute->getExportStatus();
        $shopId = $attribute->getShopId();
        if (empty($status) || !empty($shopId)) {
            return;
        }

        // if status is delete,
        // article should not be updated in bepado
        if ($status == 'delete') {
            return;
        }

        // Mark the product for bepado update
        try {
            if ($model instanceof \Shopware\Models\Article\Detail) {
                $this->getBepadoExport()->export(
                    array($attribute->getSourceId())
                );
            } else {
                /** @var \Shopware\Models\Article\Detail $detail */
                foreach ($model->getDetails() as $detail) {
                    $bepadoAttribute = $this->getHelper()->getBepadoAttributeByModel($detail);
                    $this->getBepadoExport()->export(
                        array($bepadoAttribute->getSourceId())
                    );
                }
            }
        } catch (\Exception $e) {
            // If the update fails due to missing requirements
            // (e.g. category assignment), continue without error
        }
    }
}