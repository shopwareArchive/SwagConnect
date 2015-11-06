<?php

namespace ShopwarePlugins\Connect\Subscribers;
use Shopware\Models\Attribute\ArticlePrice;
use Shopware\Models\Customer\Group;

/**
 * Class Article
 * @package ShopwarePlugins\Connect\Subscribers
 */
class Article extends BaseSubscriber
{
    public function getSubscribedEvents()
    {
        return array(
            'Shopware_Controllers_Backend_Article::preparePricesAssociatedData::after' => 'enforceConnectPriceWhenSaving',
            'Enlight_Controller_Action_PostDispatch_Backend_Article' => 'extendBackendArticle',
            'Enlight_Controller_Action_PreDispatch_Frontend_Detail' => 'extendFrontendArticle'
        );
    }

    /** @var  Group */
    protected $customerGroupRepository;

    /**
     * @return \Shopware\Components\Model\ModelRepository|Group
     */
    public function getCustomerGroupRepository()
    {
        if (!$this->customerGroupRepository) {
            $this->customerGroupRepository = Shopware()->Models()->getRepository('Shopware\Models\Customer\Group');
        }
        return $this->customerGroupRepository;
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

        switch($request->getActionName()) {
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

//                if (\Shopware::VERSION != '__VERSION__' && version_compare(\Shopware::VERSION, '4.2.2', '<')) {
                    $subject->View()->assign('disableConnectPrice', 'true');
//
//                    $subject->View()->extendsTemplate(
//                        'backend/article/model/price_attribute_connect.js'
//                    );
//                }

                $subject->View()->extendsTemplate(
                    'backend/article/view/detail/connect_tab.js'
                );
                $subject->View()->extendsTemplate(
                    'backend/article/view/detail/prices_connect.js'
                );
                $subject->View()->extendsTemplate(
                    'backend/article/controller/detail_connect.js'
                );
                break;
            default:
                break;
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
        /** @var \Shopware\Models\Article\Article $article */
        $article = $args->get('article');

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
}