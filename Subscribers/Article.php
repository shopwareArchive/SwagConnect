<?php

namespace Shopware\Bepado\Subscribers;
use Shopware\Models\Attribute\ArticlePrice;

/**
 * Class Article
 * @package Shopware\Bepado\Subscribers
 */
class Article extends BaseSubscriber
{
    public function getSubscribedEvents()
    {
        return array(
            'Shopware_Controllers_Backend_Article::getPrices::after' => 'onEnforcePriceAttributes'
        );
    }

    /**
     * Make sure, that any price has a price attribute array, even if it is not in the database, yet
     *
     * @param \Enlight_Hook_HookArgs $args
     */
    public function onEnforcePriceAttributes(\Enlight_Hook_HookArgs $args)
    {
        $prices = $args->getReturn();

        foreach ($prices as &$price) {
            if ($price['attribute'] == null) {
                $model = new ArticlePrice();
                $price['attribute'] = Shopware()->Models()->toArray($model);
            }
        }
        
        $args->setReturn($prices);
    }


}