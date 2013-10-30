<?php

namespace Shopware\Bepado\Subscribers;

class Lifecycle extends BaseSubscriber
{

    public function getSubscribedEvents()
    {
        return array(
            'Shopware\Models\Article\Article::postPersist' => 'onUpdateArticle',
            'Shopware\Models\Article\Article::postUpdate' => 'onUpdateArticle'
        );
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
        $id = $entity->getId();

        $model = $this->getHelper()->getArticleModelById($id);

        // Check if we have a valid model
        if (!$model || !$model->getAttribute()) {
            return;
        }

        // Check if entity is a bepado product
        $status = $model->getAttribute()->getBepadoExportStatus();
        if (empty($status)) {
            return;
        }

        // Mark the product for bepado update
        $this->getHelper()->insertOrUpdateProduct(array($id));
    }
}