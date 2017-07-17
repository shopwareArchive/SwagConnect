<?php

namespace ShopwarePlugins\Connect\Subscribers;
use Shopware\CustomModels\Connect\Attribute;
use ShopwarePlugins\Connect\Components\Config;
use Shopware\Connect\Struct\Change\FromShop\MakeMainVariant;
use Shopware\Models\Customer\Group;
use Shopware\Connect\Gateway;
use Shopware\Components\Model\ModelManager;
use ShopwarePlugins\Connect\Components\ConnectExport;
use Shopware\Models\Article\Article as ArticleModel;
use ShopwarePlugins\Connect\Components\Helper;
use ShopwarePlugins\Connect\Components\ProductStream\ProductStreamsAssignments;
use ShopwarePlugins\Connect\Components\VariantRegenerator;

/**
 * Class Article
 * @package ShopwarePlugins\Connect\Subscribers
 */
class Article extends BaseSubscriber
{
    /**
     * @var \Shopware\Connect\Gateway\PDO
     */
    private $connectGateway;

    /**
     * @var \Shopware\Components\Model\ModelManager
     */
    private $modelManager;

    /**
     * @var \Shopware\Models\Customer\Group
     */
    private $customerGroupRepository;

    /**
     * @var \Shopware\Models\Article\Detail
     */
    private $detailRepository;

    /**
     * @var \ShopwarePlugins\Connect\Components\ConnectExport
     */
    private $connectExport;

    /**
     * @var VariantRegenerator
     */
    private $variantRegenerator;

    /**
     * @var Helper
     */
    private $helper;

    /**
     * @var Config
     */
    private $config;

    public function __construct(
        Gateway $connectGateway,
        ModelManager $modelManager,
        ConnectExport $connectExport,
        VariantRegenerator $variantRegenerator,
        Helper $helper,
        Config $config
    ) {
        parent::__construct();
        $this->connectGateway = $connectGateway;
        $this->modelManager = $modelManager;
        $this->connectExport = $connectExport;
        $this->variantRegenerator = $variantRegenerator;
        $this->helper = $helper;
        $this->config = $config;
    }

    public function getSubscribedEvents()
    {
        return array(
            'Shopware_Controllers_Backend_Article::preparePricesAssociatedData::after' => 'enforceConnectPriceWhenSaving',
            'Enlight_Controller_Action_PostDispatch_Backend_Article' => 'extendBackendArticle',
            'Enlight_Controller_Action_PreDispatch_Backend_Article' => 'preBackendArticle',
            'Enlight_Controller_Action_PostDispatch_Frontend_Detail' => 'modifyConnectArticle',
            'Enlight_Controller_Action_PreDispatch_Frontend_Detail' => 'extendFrontendArticle'
        );
    }

    /**
     * @return \Shopware\Models\Article\Detail
     */
    public function getDetailRepository()
    {
        if (!$this->detailRepository) {
            $this->detailRepository = $this->modelManager->getRepository('Shopware\Models\Article\Detail');
        }

        return $this->detailRepository;
    }

    /**
     * @return \Shopware\Components\Model\ModelRepository|Group
     */
    public function getCustomerGroupRepository()
    {
        if (!$this->customerGroupRepository) {
            $this->customerGroupRepository = $this->modelManager->getRepository('Shopware\Models\Customer\Group');
        }
        return $this->customerGroupRepository;
    }

    /**
     * @param \Enlight_Event_EventArgs $args
     */
    public function preBackendArticle(\Enlight_Event_EventArgs $args)
    {
        /** @var $subject \Enlight_Controller_Action */
        $subject = $args->getSubject();
        $request = $subject->Request();

        switch ($request->getActionName()) {
            case 'saveDetail':
                if ($request->getParam('standard')) {
                    $this->generateMainVariantChange($request->getParam('id'));
                }
                break;
            case 'createConfiguratorVariants':
                if (!$articleId = $request->getParam('articleId')) {
                    return;
                }

                $this->variantRegenerator->setInitialSourceIds(
                    $articleId,
                    $this->helper->getArticleSourceIds([$articleId])
                );
                break;
        }
    }

    /**
     * @event Enlight_Controller_Action_PostDispatch_Backend_Article
     * @param \Enlight_Event_EventArgs $args
     */
    public function extendBackendArticle(\Enlight_Event_EventArgs $args)
    {
        /** @var $subject \Enlight_Controller_Action */
        $subject = $args->getSubject();
        $request = $subject->Request();

        switch ($request->getActionName()) {
            case 'index':
                $this->registerMyTemplateDir();
                $this->registerMySnippets();
                $subject->View()->extendsTemplate(
                    'backend/article/connect.js'
                );
                break;
            case 'load':
                $this->registerMyTemplateDir();
                $this->registerMySnippets();
                $subject->View()->extendsTemplate(
                    'backend/article/model/attribute_connect.js'
                );
                $subject->View()->assign('disableConnectPrice', 'true');
                $subject->View()->extendsTemplate(
                    'backend/article/view/detail/connect_tab.js'
                );
                $subject->View()->extendsTemplate(
                    'backend/article/view/detail/prices_connect.js'
                );
                $subject->View()->extendsTemplate(
                    'backend/article/controller/detail_connect.js'
                );
                $subject->View()->extendsTemplate(
                    'backend/article/view/detail/connect_properties.js'
                );
                break;
            case 'setPropertyList':
                // property values are saved in different ajax call then
                // property group and this will generate wrong Connect changes.
                // after the property values are saved, the temporary property group is no needed
                // and it will generate right Connect changes
                $articleId = $request->getParam('articleId', null);

                /** @var ArticleModel $article */
                $article = $this->modelManager->find(ArticleModel::class, $articleId);

                if (!$article) {
                    return;
                }

                if (!$article->getPropertyGroup()) {
                    return;
                }

                // Check if entity is a connect product
                $attribute = $this->helper->getConnectAttributeByModel($article);
                if (!$attribute) {
                    return;
                }

                // if article is not exported to Connect
                // don't need to generate changes
                if (!$this->helper->isProductExported($attribute) || !empty($attribute->getShopId())) {
                    return;
                }

                if (!$this->hasPriceType()) {
                    return;
                }

                $detail = $article->getMainDetail();

                if ($detail->getAttribute()->getConnectPropertyGroup()) {
                    $detail->getAttribute()->setConnectPropertyGroup(null);
                    $this->modelManager->persist($detail);
                    $this->modelManager->flush();
                }

                $sourceIds = Shopware()->Db()->fetchCol(
                    'SELECT source_id FROM s_plugin_connect_items WHERE article_id = ?',
                    array($article->getId())
                );

                $this->connectExport->export($sourceIds);
                break;
            case 'createConfiguratorVariants':
                // main detail should be updated as well, because shopware won't call lifecycle event
                // even postUpdate of Detail. By this way Connect will generate change for main variant,
                // otherwise $product->variant property is an empty array
                // if main detail is not changed, Connect SDK won't generate change for it.
                // ticket CON-3747
                if (!$articleId = $request->getParam('articleId')) {
                    return;
                }

                $this->regenerateChangesForArticle($articleId);
                break;
            case 'getPropertyList':
                $subject->View()->data = $this->addConnectFlagToProperties(
                    $subject->View()->data
                );
                break;
            case 'deleteAllVariants':
                if ($articleId = $request->getParam('articleId')) {
                    /** @var ArticleModel $article */
                    $article = $this->modelManager->find(ArticleModel::class, (int) $articleId);
                    if (!$article) {
                        return;
                    }

                    $this->deleteVariants($articleId);
                }
                break;
            default:
                break;
        }
    }

    /**
     * @param int $articleId
     */
    public function regenerateChangesForArticle($articleId)
    {
        $autoUpdateProducts = $this->config->getConfig('autoUpdateProducts', Config::UPDATE_AUTO);
        if ($autoUpdateProducts == Config::UPDATE_MANUAL) {
            return;
        }

        /** @var \Shopware\Models\Article\Article $article */
        $article = $this->modelManager->getRepository(ArticleModel::class)->find((int)$articleId);
        if (!$article) {
            return;
         }

        $attribute = $this->helper->getConnectAttributeByModel($article);
        if (!$attribute) {
            return;
        }

        // Check if entity is a connect product
        if (!$this->helper->isProductExported($attribute)) {
            return;
        }

        $this->variantRegenerator->setCurrentSourceIds(
            $articleId,
            $this->helper->getArticleSourceIds([$articleId])
        );
        $this->variantRegenerator->generateChanges($articleId);
    }

    /**
     * Delete all variants of given product except main one
     *
     * @param int $articleId
     */
    private function deleteVariants($articleId)
    {
        $autoUpdateProducts = $this->config->getConfig('autoUpdateProducts', Config::UPDATE_AUTO);
        if ($autoUpdateProducts == Config::UPDATE_MANUAL) {
            return;
        }

        /** @var \Shopware\Models\Article\Article $article */
        $article = $this->modelManager->getRepository(ArticleModel::class)->find((int)$articleId);
        if (!$article) {
            return;
        }

        $connectAttribute = $this->helper->getConnectAttributeByModel($article);
        if (!$connectAttribute) {
            return;
        }

        // Check if entity is a connect product
        if (!$this->helper->isProductExported($connectAttribute)) {
            return;
        }

        $mainVariantSourceId = $connectAttribute->getSourceId();
        $sourceIds = array_filter(
            $this->helper->getArticleSourceIds([$article->getId()]),
            function ($sourceId) use ($mainVariantSourceId) {
                return $sourceId != $mainVariantSourceId;
            }
        );

        foreach ($sourceIds as $sourceId) {
            $this->getSDK()->recordDelete($sourceId);
        }

        $this->connectExport->updateConnectItemsStatus($sourceIds, Attribute::STATUS_DELETE);
    }

    public function addConnectFlagToProperties($data)
    {
        $groups = [];
        foreach ($data as $group) {
            $options = [];
            foreach ($group['value'] as $value) {
                $element = $value;
                $optionId = $value['id'];
                $valueModel = $this->modelManager->getRepository('Shopware\Models\Property\Value')->find($optionId);

                $attribute = null;
                if ($valueModel) {
                    $attribute = $valueModel->getAttribute();
                }

                if ($attribute && $attribute->getConnectIsRemote()) {
                    $element['connect'] = true;
                } else {
                    $element['connect'] = false;
                }
                $options[] = $element;
            }

            $group['value'] = $options;
            $groups[] = $group;
        }

        return $groups;
    }

    /**
     * @param $detailId
     */
    public function generateMainVariantChange($detailId)
    {
        $detail = $this->getDetailRepository()->findOneBy(array('id' => $detailId));

        if (!$detail instanceof \Shopware\Models\Article\Detail) {
            return;
        }

        //if it is already main variant dont generate MakeMainVariant change
        if ($detail->getKind() == 1) {
            return;
        }

        $attribute = $this->helper->getConnectAttributeByModel($detail);

        if (!$attribute) {
            return;
        }
        // Check if entity is a connect product
        if (!$this->helper->isProductExported($attribute)) {
            return;
        }

        if (!$this->hasPriceType()) {
            return;
        }

        $groupId = $attribute->getGroupId() ? $attribute->getGroupId() : $attribute->getArticleId();

        $mainVariant = new MakeMainVariant(array(
            'sourceId' => $attribute->getSourceId(),
            'groupId' => $groupId
        ));

        try {
            $this->getSDK()->makeMainVariant($mainVariant);
        } catch (\Exception $e) {
            // if sn is not available, proceed without exception
        }
    }

    /**
     * When saving prices make sure, that the connectPrice is stored in net
     *
     * @param \Enlight_Hook_HookArgs $args
     */
    public function enforceConnectPriceWhenSaving(\Enlight_Hook_HookArgs $args)
    {
        /** @var array $prices */
        $prices = $args->getReturn();

        $connectCustomerGroup = $this->getConnectCustomerGroup();
        if (!$connectCustomerGroup) {
            return;
        }
        $connectCustomerGroupKey = $connectCustomerGroup->getKey();
        $defaultPrices = array();
        foreach ($prices as $key => $priceData) {
            if ($priceData['customerGroupKey'] == $connectCustomerGroupKey) {
                return;
            }
            if ($priceData['customerGroupKey'] == 'EK') {
                $defaultPrices[] = $priceData;
            }
        }

        foreach ($defaultPrices as $price) {
            $prices[] = array(
                'from' => $price['from'],
                'to' => $price['to'],
                'price' => $price['price'],
                'pseudoPrice' => $price['pseudoPrice'],
                'basePrice' => $price['basePrice'],
                'percent' => $price['percent'],
                'customerGroup' => $connectCustomerGroup,
                'article' => $price['article'],
                'articleDetail' => $price['articleDetail'],
            );
        }

        $args->setReturn($prices);
    }

    /**
     * @return \Shopware\Models\Customer\Group|null
     */
    public function getConnectCustomerGroup()
    {
        $repo = Shopware()->Models()->getRepository('Shopware\Models\Attribute\CustomerGroup');
        /** @var \Shopware\Models\Attribute\CustomerGroup $model */
        $model = $repo->findOneBy(array('connectGroup' => true));

        $customerGroup = null;
        if ($model && $model->getCustomerGroup()) {
            return $model->getCustomerGroup();
        }

        return null;
    }

    /**
     * Load article detail
     *
     * @param \Enlight_Event_EventArgs $args
     */
    public function extendFrontendArticle(\Enlight_Event_EventArgs $args)
    {
        /** @var \Enlight_Controller_Request_RequestHttp $request */
        $request = $args->getSubject()->Request();
        if ($request->getActionName() != 'index') {
            return;
        }

        $detailId = (int) $request->sArticleDetail;
        if ($detailId === 0) {
            return;
        }

        /** @var \Shopware\Models\Article\Detail $detailModel */
        $detailModel = Shopware()->Models()->getRepository('Shopware\Models\Article\Detail')->find($detailId);
        if (!$detailModel) {
            return;
        }

        $params = array();
        /** @var \Shopware\Models\Article\Configurator\Option $option */
        foreach ($detailModel->getConfiguratorOptions() as $option) {
            $groupId = $option->getGroup()->getId();
            $params[$groupId] = $option->getId();
        }
        $request->setPost('group', $params);
    }

    /**
     * Should be possible to buy connect products
     * when they're not in stock.
     * Depends on remote shop configuration.
     *
     * @param \Enlight_Event_EventArgs $args
     */
    public function modifyConnectArticle(\Enlight_Event_EventArgs $args)
    {
        /** @var \Enlight_Controller_Request_RequestHttp $request */
        $request = $args->getSubject()->Request();

        if ($request->getActionName() != 'index') {
            return;
        }
        $subject = $args->getSubject();
        $article = $subject->View()->getAssign('sArticle');
        if (!$article) {
            return;
        }

        // when article stock is greater than 0
        // we don't need to modify it.
        if ($article['instock'] > 0) {
            return;
        }

        $articleId = $article['articleID'];
        $remoteShopId = $this->getRemoteShopId($articleId);
        if (!$remoteShopId) {
            // article is not imported via Connect
            return;
        }

        /** @var \Shopware\Models\Article\Article $articleModel */
        $articleModel = Shopware()->Models()->getRepository('Shopware\Models\Article\Article')->find($articleId);
        if (!$articleModel) {
            return;
        }

        $shopConfiguration = $this->connectGateway->getShopConfiguration($remoteShopId);
        if ($shopConfiguration->sellNotInStock && !$articleModel->getLastStock()) {
            // if selNotInStock is = true and article getLastStock = false
            // we don't need to modify it
            return;
        }

        if (!$shopConfiguration->sellNotInStock && $articleModel->getLastStock()) {
            // if sellNotInStock is = false and article getLastStock = true
            // we don't need to modify it
            return;
        }

        // sellNotInStock is opposite on articleLastStock
        // when it's true, lastStock must be false
        $articleModel->setLastStock(!$shopConfiguration->sellNotInStock);
        Shopware()->Models()->persist($articleModel);
        Shopware()->Models()->flush();

        // modify assigned article
        if ($shopConfiguration->sellNotInStock) {
            $article['laststock'] = false;
            $article['instock'] = 100;
            $article['isAvailable'] = true;
        } else {
            $article['laststock'] = true;
        }
        $subject->View()->assign('sArticle', $article);
    }

    /**
     * Not using the default helper-methods here, in order to keep this small and without any dependencies
     * to the SDK
     *
     * @param $id
     * @return boolean|int
     */
    private function getRemoteShopId($id)
    {
        $sql = 'SELECT shop_id FROM s_plugin_connect_items WHERE article_id = ? AND shop_id IS NOT NULL';
        return Shopware()->Db()->fetchOne($sql, array($id));
    }
}