<?php

namespace Shopware\Bepado\Subscribers;

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
            'Shopware\Models\Article\Article::preRemove' => 'onDeleteArticle'
        );
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

        if ($entity instanceof \Shopware\Models\Article\Detail) {
            $entity = $entity->getArticle();
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