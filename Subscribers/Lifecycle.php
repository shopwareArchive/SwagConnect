<?php

namespace ShopwarePlugins\Connect\Subscribers;

use Shopware\Connect\Struct\PaymentStatus;
use Shopware\CustomModels\Connect\Attribute;
use ShopwarePlugins\Connect\Components\Config;
use Shopware\Components\Model\ModelManager;
use ShopwarePlugins\Connect\Components\ErrorHandler;
use ShopwarePlugins\Connect\Components\Utils;
use ShopwarePlugins\Connect\Components\ConnectExport;
use ShopwarePlugins\Connect\Components\Validator\ProductAttributesValidator\ProductsAttributesValidator;
use Shopware\Models\Order\Order;

/**
 * Handles article lifecycle events in order to automatically update/delete products to/from connect
 *
 * Class Lifecycle
 * @package ShopwarePlugins\Connect\Subscribers
 */
class Lifecycle extends BaseSubscriber
{
    /**
     * @var \Shopware\Components\Model\ModelManager
     */
    private $manager;

    /**
     * @var int
     */
    private $autoUpdateProducts;

    /**
     * Lifecycle constructor.
     *
     * @param ModelManager $modelManager
     * @param int $autoUpdateProducts
     */
    public function __construct(ModelManager $modelManager, $autoUpdateProducts)
    {
        parent::__construct();

        $this->manager = $modelManager;
        $this->autoUpdateProducts = $autoUpdateProducts;
    }

    public function getSubscribedEvents()
    {
        return array(
            'Shopware\Models\Article\Article::preUpdate' => 'onPreUpdate',
            'Shopware\Models\Article\Article::postPersist' => 'onUpdateArticle',
            'Shopware\Models\Article\Detail::postPersist' => 'onPersistDetail',
            'Shopware\Models\Article\Article::preRemove' => 'onDeleteArticle',
            'Shopware\Models\Article\Detail::preRemove' => 'onDeleteDetail',
            'Shopware\Models\Order\Order::postUpdate' => 'onUpdateOrder',
            'Shopware\Models\Shop\Shop::preRemove' => 'onDeleteShop',
        );
    }

    /**
     * @return ConnectExport
     */
    public function getConnectExport()
    {
        return new ConnectExport(
            $this->getHelper(),
            $this->getSDK(),
            $this->manager,
            new ProductsAttributesValidator(),
            $this->getConnectConfig(),
            new ErrorHandler(),
            Shopware()->Container()->get('events')
        );
    }

    public function onPreUpdate(\Enlight_Event_EventArgs $eventArgs)
    {
        /** @var \Shopware\Models\Article\Article $entity */
        $entity = $eventArgs->get('entity');
        $db = Shopware()->Db();

        // Check if entity is a connect product
        $attribute = $this->getHelper()->getConnectAttributeByModel($entity);
        if (!$attribute) {
            return;
        }

        // if article is not exported to Connect
        // don't need to generate changes
        if (!$this->getHelper()->isProductExported($attribute) || !empty($attribute->getShopId())) {
            return;
        }

        if (!$this->hasPriceType()) {
            return;
        }

        $changeSet = $eventArgs->get('entityManager')->getUnitOfWork()->getEntityChangeSet($entity);

        // If product propertyGroup is changed we need to store the old one,
        // because product property value are still not changed and
        // this will generate wrong Connect changes
        if ($changeSet['propertyGroup']) {
            $filterGroupId = $db->fetchOne(
                "SELECT filtergroupID FROM s_articles WHERE id = ?", [$entity->getId()]
            );

            $db->executeUpdate(
                'UPDATE `s_articles_attributes` SET `connect_property_group` = ? WHERE `articledetailsID` = ?',
                [$filterGroupId, $entity->getMainDetail()->getId()]
            );
        }
    }


    /**
     * @param \Enlight_Event_EventArgs $eventArgs
     */
    public function onUpdateOrder(\Enlight_Event_EventArgs $eventArgs)
    {
        /** @var \Shopware\Models\Order\Order $order */
        $order = $eventArgs->get('entity');

        // Compute the changeset and return, if orderStatus did not change
        $changeSet = $eventArgs->get('entityManager')->getUnitOfWork()->getEntityChangeSet($order);

        if (isset($changeSet['paymentStatus'])) {
            $this->updatePaymentStatus($order);
        }

        if (isset($changeSet['orderStatus'])) {
            $this->updateOrderStatus($order);
        }
    }

    /**
     * Callback function to delete an product from connect
     * after it is going to be deleted locally
     *
     * @param \Enlight_Event_EventArgs $eventArgs
     */
    public function onDeleteArticle(\Enlight_Event_EventArgs $eventArgs)
    {
        $entity = $eventArgs->get('entity');
        $this->getConnectExport()->setDeleteStatusForVariants($entity);
    }

    /**
     * Callback function to delete product detail from connect
     * after it is going to be deleted locally
     *
     * @param \Enlight_Event_EventArgs $eventArgs
     */
    public function onDeleteDetail(\Enlight_Event_EventArgs $eventArgs)
    {
        /** @var \Shopware\Models\Article\Detail $entity */
        $entity = $eventArgs->get('entity');
        if ($entity->getKind() !== 1) {
            $attribute = $this->getHelper()->getConnectAttributeByModel($entity);
            if (!$this->getHelper()->isProductExported($attribute)) {
                return;
            }
            $this->getConnectExport()->updateConnectItemsStatus([$attribute->getSourceId()], Attribute::STATUS_DELETE);
        }
    }

    /**
     * Callback method to update changed connect products
     *
     * @param \Enlight_Event_EventArgs $eventArgs
     */
    public function onUpdateArticle(\Enlight_Event_EventArgs $eventArgs)
    {
        $entity = $eventArgs->get('entity');

        $this->handleChange($entity);
    }


    /**
     * Generate changes for Article or Detail if necessary
     *
     * @param \Shopware\Models\Article\Article | \Shopware\Models\Article\Detail $entity
     */
    public function handleChange($entity)
    {
        if (!$entity instanceof \Shopware\Models\Article\Article
            && !$entity instanceof \Shopware\Models\Article\Detail
        ) {
            return;
        }

        $id = $entity->getId();
        $className = get_class($entity);
        $model = $this->manager->getRepository($className)->find($id);
        // Check if we have a valid model
        if (!$model) {
            return;
        }

        // Check if entity is a connect product
        $attribute = $this->getHelper()->getConnectAttributeByModel($model);
        if (!$attribute) {
            return;
        }

        // if article is not exported to Connect
        // or at least one article detail from same article is not exported
        // don't need to generate changes
        if (!$this->getHelper()->isProductExported($attribute) || !empty($attribute->getShopId())) {
            if (!$this->getHelper()->hasExportedVariants($attribute)) {
                return;
            }
        }

        if (!$this->hasPriceType()) {
            return;
        }

        $forceExport = false;
        if ($entity instanceof \Shopware\Models\Article\Detail) {
            $changeSet = $this->manager->getUnitOfWork()->getEntityChangeSet($entity);
            // if detail number has been changed
            // sc plugin must generate & sync the change immediately
            if (array_key_exists('number', $changeSet)) {
                $forceExport = true;
            }
        }

        // Mark the product for connect update
        try {
            if ($model instanceof \Shopware\Models\Article\Detail) {
                $this->generateChangesForDetail($model, $forceExport);
            } elseif ($model instanceof \Shopware\Models\Article\Article){
                $this->generateChangesForArticle($model, $forceExport);
            }
        } catch (\Exception $e) {
            // If the update fails due to missing requirements
            // (e.g. category assignment), continue without error
        }
    }

    /**
     * Callback method to insert new article details in Connect system
     * Used when article is exported and after that variants are generated
     *
     * @param \Enlight_Event_EventArgs $eventArgs
     */
    public function onPersistDetail(\Enlight_Event_EventArgs $eventArgs)
    {
        /** @var \Shopware\Models\Article\Detail $detail */
        $detail = $eventArgs->get('entity');

        /** @var \Shopware\Models\Article\Article $article */
        $article = $detail->getArticle();
        $articleAttribute = $this->getHelper()->getConnectAttributeByModel($article);
        if (!$articleAttribute) {
            return;
        }

        // if article is not exported to Connect
        // don't need to generate changes
        if (!$this->getHelper()->isProductExported($articleAttribute) || !empty($articleAttribute->getShopId())) {
            return;
        }

        if (!$this->hasPriceType()) {
            return;
        }

        // Mark the article detail for connect export
        try {
            $this->getHelper()->getOrCreateConnectAttributeByModel($detail);
            $forceExport = false;
            $changeSet = $eventArgs->get('entityManager')->getUnitOfWork()->getEntityChangeSet($detail);
            // if detail number has been changed
            // sc plugin must generate & sync the change immediately
            if (array_key_exists('number', $changeSet)) {
                $forceExport = true;
            }

            $this->generateChangesForDetail($detail, $forceExport);
        } catch (\Exception $e) {
            // If the update fails due to missing requirements
            // (e.g. category assignment), continue without error
        }
    }

    /**
     * Callback function to shop from export languages
     *
     * @param \Enlight_Event_EventArgs $eventArgs
     */
    public function onDeleteShop(\Enlight_Event_EventArgs $eventArgs)
    {
        /** @var \Shopware\Models\Shop\Shop $shop */
        $shop = $eventArgs->get('entity');
        $shopId = $shop->getId();
        $exportLanguages = $this->getConnectConfig()->getConfig('exportLanguages');
        $exportLanguages = $exportLanguages ?: array();

        if (!in_array($shopId, $exportLanguages)) {
           return;
        }

        $exportLanguages = array_splice($exportLanguages, array_search($shopId, $exportLanguages), 1);
        $this->getConnectConfig()->setConfig('exportLanguages', $exportLanguages, null, 'export');
    }

    /**
     * @param \Shopware\Models\Article\Detail $detail
     * @param bool $force
     */
    private function generateChangesForDetail(\Shopware\Models\Article\Detail $detail, $force = false)
    {
        $attribute = $this->getHelper()->getConnectAttributeByModel($detail);
        if (!$detail->getActive() && $this->getConnectConfig()->getConfig('excludeInactiveProducts')) {
            $this->getConnectExport()->syncDeleteDetail($detail);
            return;
        }

        if ($this->autoUpdateProducts == 1 || $force === true) {
            $this->getConnectExport()->export(
                array($attribute->getSourceId()), null, true
            );
        } elseif ($this->autoUpdateProducts == 2) {
            $this->manager->getConnection()->update(
                's_plugin_connect_items',
                array('cron_update' => 1),
                array('article_detail_id' => $detail->getId())
            );
        }
    }

    private function generateChangesForArticle(\Shopware\Models\Article\Article $article, $force = false)
    {
        if (!$article->getActive() && $this->getConnectConfig()->getConfig('excludeInactiveProducts')) {
            $this->getConnectExport()->setDeleteStatusForVariants($article);
            return;
        }

        if ($this->autoUpdateProducts == 1 || $force === true) {
            $sourceIds = $this->getHelper()->getSourceIdsFromArticleId($article->getId());

            $this->getConnectExport()->export($sourceIds, null, true);
        } elseif ($this->autoUpdateProducts == 2) {
            $this->manager->getConnection()->update(
                's_plugin_connect_items',
                array('cron_update' => 1),
                array('article_id' => $article->getId())
            );
        }
    }

    /**
     * Sends the new order status when supplier change it
     *
     * @param Order $order
     */
    private function updateOrderStatus(Order $order)
    {
        $attribute = $order->getAttribute();
        if (!$attribute || !$attribute->getConnectShopId()) {
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
     * Sends the new payment status when merchant change it
     *
     * @param Order $order
     */
    private function updatePaymentStatus(Order $order)
    {
        $orderUtil = new Utils\ConnectOrderUtil();
        if (!$orderUtil->hasLocalOrderConnectProducts($order->getId())) {
            return;
        }

        $paymentStatusMapper = new Utils\OrderPaymentStatusMapper();
        $paymentStatus = $paymentStatusMapper->getPaymentStatus($order);

        $this->generateChangeForPaymentStatus($paymentStatus);
    }

    /**
     * @param PaymentStatus $paymentStatus
     */
    private function generateChangeForPaymentStatus(PaymentStatus $paymentStatus)
    {
        try {
            $this->getSDK()->updatePaymentStatus($paymentStatus);
        } catch (\Exception $e) {
            // if sn is not available, proceed without exception
        }
    }
}