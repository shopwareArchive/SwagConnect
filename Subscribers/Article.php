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
            'Shopware_Controllers_Backend_Article::getPrices::after' => 'onEnforcePriceAttributes',
            'Shopware_Controllers_Backend_Article::preparePricesAssociatedData::after' => 'fixTaxRatesWhenSaving',
            'Shopware_Controllers_Backend_Article::formatPricesFromNetToGross::after' => 'fixTaxRatesWhenLoading',
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
     * Make sure, that any price has a price attribute array, even if it is not in the database, yet
     *
     * @param \Enlight_Hook_HookArgs $args
     */
    public function onEnforcePriceAttributes(\Enlight_Hook_HookArgs $args)
    {
        if (!$this->bepadoPricePossible()) {
            return;
        }

        $prices = $args->getReturn();

        foreach ($prices as &$price) {
            if ($price['attribute'] == null) {
                $model = new ArticlePrice();
                $price['attribute'] = Shopware()->Models()->toArray($model);
            }
        }

        $args->setReturn($prices);
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
    public function fixTaxRatesWhenSaving(\Enlight_Hook_HookArgs $args)
    {
        if (!$this->bepadoPricePossible()) {
            return;
        }

        /** @var array $prices */
        $prices = $args->getReturn();
        /** @var \Shopware\Models\Article\Article $article */
        $article = $args->get('article');
        /** @var \Shopware\Models\Tax\Tax $tax */
        $tax = $args->get('tax');

        foreach ($prices as $key => &$priceData) {

            if (!isset($priceData['attribute'])) {
                continue;
            }

            /** @var \Shopware\Models\Customer\Group $customerGroup */
            $customerGroup = $this->getCustomerGroupRepository()->findOneBy(array('key' => $priceData['customerGroupKey']));

            if ($customerGroup->getTaxInput()) {
                $priceData['attribute']['bepadoPrice'] = $priceData['attribute']['bepadoPrice'] / (100 + $tax->getTax()) * 100;
            }
        }

        $args->setReturn($prices);
    }

    /**
     * When loading prices make sure, that the tax rate is added
     *
     * @param \Enlight_Hook_HookArgs $args
     */
    public function fixTaxRatesWhenLoading(\Enlight_Hook_HookArgs $args)
    {
        if (!$this->bepadoPricePossible()) {
            return;
        }

        /** @var array $prices */
        $prices = $args->getReturn();
        $tax = $args->get('tax');

        foreach ($prices as $key => $price) {
            if (!isset($price['attribute'])) {
                continue;
            }

            $customerGroup = $price['customerGroup'];
            if ($customerGroup['taxInput']) {
                $price['attribute']['bepadoPrice'] = $price['attribute']['bepadoPrice'] / 100 * (100 + $tax['tax']) ;
            }
            $prices[$key] = $price;
        }


        $args->setReturn($prices);
    }

    /**
     * Check if the current shopware version allows price attributes without problems
     *
     * @return bool
     */
    public function bepadoPricePossible()
    {
        return false;

        return version_compare(\Shopware::VERSION, '4.2.2', '>=')
                || \Shopware::VERSION == '__VERSION__';
    }
}