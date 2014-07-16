<?php

namespace Shopware\Bepado\Subscribers;
use Shopware\Models\Attribute\ArticlePrice;
use Shopware\Models\Customer\Group;

/**
 * Class Article
 * @package Shopware\Bepado\Subscribers
 */
class Article extends BaseSubscriber
{
    public function getSubscribedEvents()
    {
        return array(
            'Shopware_Controllers_Backend_Article::preparePricesAssociatedData::after' => 'enforceBepadoPriceWhenSaving',
            'Enlight_Controller_Action_PostDispatch_Backend_Article' => 'extendBackendArticle'
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
                    'backend/article/bepado.js'
                );
                break;
            case 'load':
                $this->registerMyTemplateDir();
                $this->registerMySnippets();
                $subject->View()->extendsTemplate(
                    'backend/article/model/attribute_bepado.js'
                );

//                if (\Shopware::VERSION != '__VERSION__' && version_compare(\Shopware::VERSION, '4.2.2', '<')) {
                    $subject->View()->assign('disableBepadoPrice', 'true');
//
//                    $subject->View()->extendsTemplate(
//                        'backend/article/model/price_attribute_bepado.js'
//                    );
//                }

                $subject->View()->extendsTemplate(
                    'backend/article/view/detail/bepado_tab.js'
                );
                $subject->View()->extendsTemplate(
                    'backend/article/view/detail/prices_bepado.js'
                );
                $subject->View()->extendsTemplate(
                    'backend/article/view/detail/settings_bepado.js'
                );
                $subject->View()->extendsTemplate(
                    'backend/article/controller/detail_bepado.js'
                );
                break;
            default:
                break;
        }
    }


    /**
     * When saving prices make sure, that the bepadoPrice is stored in net
     *
     * @param \Enlight_Hook_HookArgs $args
     */
    public function enforceBepadoPriceWhenSaving(\Enlight_Hook_HookArgs $args)
    {

        /** @var array $prices */
        $prices = $args->getReturn();
        /** @var \Shopware\Models\Article\Article $article */
        $article = $args->get('article');

        $bepadoCustomerGroup = $this->getBepadoCustomerGroup();
        $bepadoCustomerGroupKey = $bepadoCustomerGroup->getKey();
        $defaultPrices = array();

        if (!$bepadoCustomerGroup) {
            return;
        }

        foreach ($prices as $key => $priceData) {
            if ($priceData['customerGroupKey'] == $bepadoCustomerGroupKey) {
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
                'customerGroup' => $bepadoCustomerGroup,
                'article' => $price['article'],
                'articleDetail' => $price['articleDetail'],
            );
        }

        $args->setReturn($prices);
    }

    /**
     * @return int|null
     */
    public function getBepadoCustomerGroup()
    {
        $repo = Shopware()->Models()->getRepository('Shopware\Models\Attribute\CustomerGroup');
        /** @var \Shopware\Models\Attribute\CustomerGroup $model */
        $model = $repo->findOneBy(array('bepadoGroup' => true));

        $customerGroup = null;
        if ($model && $model->getCustomerGroup()) {
            return $model->getCustomerGroup();
        }

        return null;
    }
}