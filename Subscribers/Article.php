<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Subscribers;

use Shopware\Connect\SDK;
use Enlight\Event\SubscriberInterface;
use Shopware\CustomModels\Connect\Attribute;
use ShopwarePlugins\Connect\Components\Config;
use Shopware\Connect\Struct\Change\FromShop\MakeMainVariant;
use Shopware\Models\Customer\Group;
use Shopware\Connect\Gateway;
use Shopware\Components\Model\ModelManager;
use ShopwarePlugins\Connect\Components\ConnectExport;
use Shopware\Models\Article\Article as ArticleModel;
use ShopwarePlugins\Connect\Components\Helper;
use Shopware\Models\Article\Detail;
use Shopware\Models\Attribute\CustomerGroup as CustomerGroupAttribute;
use ShopwarePlugins\Connect\Services\RemoteShopService;

/**
 * Class Article
 * @package ShopwarePlugins\Connect\Subscribers
 */
class Article implements SubscriberInterface
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
     * @var Helper
     */
    private $helper;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var SDK
     */
    private $sdk;

    /**
     * @param Gateway $connectGateway
     * @param ModelManager $modelManager
     * @param ConnectExport $connectExport
     * @param Helper $helper
     * @param Config $config
     * @param SDK $sdk
     */
    public function __construct(
        Gateway $connectGateway,
        ModelManager $modelManager,
        ConnectExport $connectExport,
        Helper $helper,
        Config $config,
        SDK $sdk
    ) {
        $this->connectGateway = $connectGateway;
        $this->modelManager = $modelManager;
        $this->connectExport = $connectExport;
        $this->helper = $helper;
        $this->config = $config;
        $this->sdk = $sdk;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            'Shopware_Controllers_Backend_Article::preparePricesAssociatedData::after' => 'enforceConnectPriceWhenSaving',
            'Enlight_Controller_Action_PostDispatch_Backend_Article' => 'extendBackendArticle',
            'Enlight_Controller_Action_PreDispatch_Backend_Article' => 'preBackendArticle',
            'Enlight_Controller_Action_PostDispatch_Frontend_Detail' => 'modifyConnectArticle',
            'Enlight_Controller_Action_PreDispatch_Frontend_Detail' => 'extendFrontendArticle',
            'Shopware_Modules_Basket_AddArticle_Start' => 'checkSupplierPluginAvailability'
        ];
    }

    /**
     * @param \Enlight_Event_EventArgs $args
     * @throws \Exception
     * @return bool|void
     */
    public function checkSupplierPluginAvailability(\Enlight_Event_EventArgs $args)
    {
        $articleDetail = $this->helper->getDetailByNumber($args->getId());
        if (!$articleDetail instanceof Detail) {
            return;
        }

        $articleDetailId = $articleDetail->getId();

        if (!$this->helper->isRemoteArticleDetail($articleDetailId)) {
            return;
        }

        $shopProductId = $this->helper->getShopProductId($articleDetailId);
        $shopId = $shopProductId->shopId;

        /**
         * @var RemoteShopService
         * @todo: refactor when using 5.2 plugin base.
         */
        $remoteShopService = Shopware()->Container()->get('swagconnect.remote_shop_service');

        if ($remoteShopService->isPingRemoteShopSuccessful($shopId)) {
            return;
        }

        $this->createBasketInfoMessagesOnFailingRemoteShopPing();

        // Prevent adding article to basket
        return false;
    }

    private function createBasketInfoMessagesOnFailingRemoteShopPing()
    {
        $infoMessage = Shopware()->Snippets()->getNamespace('backend/connect/view/main')->get(
            'connect/basket/addArticleFailedInfoMessage',
            'The marketplace product could not be added to the basket because it is not available.'
        );

        Shopware()->Template()
            ->assign('basketInfoMessage', $infoMessage)
            ->assign('sBasketInfo', $infoMessage);
    }

    /**
     * @return \Shopware\Models\Article\Detail
     */
    public function getDetailRepository()
    {
        if (!$this->detailRepository) {
            $this->detailRepository = $this->modelManager->getRepository(Detail::class);
        }

        return $this->detailRepository;
    }

    /**
     * @return \Shopware\Components\Model\ModelRepository|Group
     */
    public function getCustomerGroupRepository()
    {
        if (!$this->customerGroupRepository) {
            $this->customerGroupRepository = $this->modelManager->getRepository(Group::class);
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

                $this->deleteVariants($articleId);
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
                $subject->View()->extendsTemplate(
                    'backend/article/connect.js'
                );
                break;
            case 'load':
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

                if (!SDK::isPriceTypeValid($this->sdk->getPriceType())) {
                    return;
                }

                $detail = $article->getMainDetail();

                if ($detail->getAttribute()->getConnectPropertyGroup()) {
                    $detail->getAttribute()->setConnectPropertyGroup(null);
                    $this->modelManager->persist($detail);
                    $this->modelManager->flush();
                }

                $autoUpdateProducts = $this->config->getConfig('autoUpdateProducts');
                if ($autoUpdateProducts == Config::UPDATE_CRON_JOB) {
                    $this->modelManager->getConnection()->update(
                        's_plugin_connect_items',
                        ['cron_update' => 1],
                        ['article_id' => $article->getId()]
                    );
                } else {
                    $sourceIds = $this->modelManager->getConnection()->executeQuery(
                        'SELECT source_id FROM s_plugin_connect_items WHERE article_id = ?',
                        [$article->getId()]
                    )->fetchAll(\PDO::FETCH_COLUMN);
                    $this->connectExport->export($sourceIds);
                }
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
        $article = $this->modelManager->getRepository(ArticleModel::class)->find((int) $articleId);
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

        if ($autoUpdateProducts == Config::UPDATE_CRON_JOB) {
            $this->connectExport->markArticleForCronUpdate($articleId);

            return;
        }

        $this->connectExport->export($this->helper->getArticleSourceIds([$articleId]));
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
        $article = $this->modelManager->getRepository(ArticleModel::class)->find((int) $articleId);
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
            $this->sdk->recordDelete($sourceId);
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
        $detail = $this->getDetailRepository()->findOneBy(['id' => $detailId]);

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

        if (!SDK::isPriceTypeValid($this->sdk->getPriceType())) {
            return;
        }

        $groupId = $attribute->getGroupId() ? $attribute->getGroupId() : $attribute->getArticleId();

        $mainVariant = new MakeMainVariant([
            'sourceId' => $attribute->getSourceId(),
            'groupId' => $groupId
        ]);

        try {
            $this->sdk->makeMainVariant($mainVariant);
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
        $defaultPrices = [];
        foreach ($prices as $key => $priceData) {
            if ($priceData['customerGroupKey'] == $connectCustomerGroupKey) {
                return;
            }
            if ($priceData['customerGroupKey'] == 'EK') {
                $defaultPrices[] = $priceData;
            }
        }

        foreach ($defaultPrices as $price) {
            $prices[] = [
                'from' => $price['from'],
                'to' => $price['to'],
                'price' => $price['price'],
                'pseudoPrice' => $price['pseudoPrice'],
                'basePrice' => $price['basePrice'],
                'percent' => $price['percent'],
                'customerGroup' => $connectCustomerGroup,
                'article' => $price['article'],
                'articleDetail' => $price['articleDetail'],
            ];
        }

        $args->setReturn($prices);
    }

    /**
     * @return \Shopware\Models\Customer\Group|null
     */
    public function getConnectCustomerGroup()
    {
        $repo = $this->modelManager->getRepository(CustomerGroupAttribute::class);
        /** @var \Shopware\Models\Attribute\CustomerGroup $model */
        $model = $repo->findOneBy(['connectGroup' => true]);

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
        $detailModel = $this->modelManager->getRepository(Detail::class)->find($detailId);
        if (!$detailModel) {
            return;
        }

        $params = [];
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
        $articleModel = $this->modelManager->getRepository(ArticleModel::class)->find($articleId);
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
        $this->modelManager->persist($articleModel);
        $this->modelManager->flush();

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
     * @param $articleId
     * @return bool|int
     */
    private function getRemoteShopId($articleId)
    {
        return $this->modelManager->getConnection()->fetchColumn(
            'SELECT shop_id FROM s_plugin_connect_items WHERE article_id = ? AND shop_id IS NOT NULL',
            [$articleId]
        );
    }
}
