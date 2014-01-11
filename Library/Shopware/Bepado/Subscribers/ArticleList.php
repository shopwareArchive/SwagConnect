<?php

namespace Shopware\Bepado\Subscribers;

/**
 * Implements a 'bepado' filter for the article list
 *
 * Class ArticleList
 * @package Shopware\Bepado\Subscribers
 */
class ArticleList extends BaseSubscriber
{
    public function getSubscribedEvents()
    {
        return array(
            'Shopware_Controllers_Backend_ArticleList_SQLParts' => 'onFilterArticle',
        );
    }


    /**
     * If the 'bepado' filter is checked, only show products imported from bepado
     *
     * @param   \Enlight_Event_EventArgs $args
     */
    public function onFilterArticle(\Enlight_Event_EventArgs $args)
    {
        $subject = $args->getSubject();
        $filterBy = $subject->Request()->getParam('filterBy');

        list($sqlParams, $filterSql, $categorySql, $imageSQL, $order) = $args->getReturn();

        if ($filterBy == 'bepado') {
            $imageSQL = "
                LEFT JOIN s_plugin_bepado_items as bepado_items
                ON bepado_items.article_id = articles.id
            ";

            $filterSql .= " AND bepado_items.shop_id > 0 ";
        }

        return array($sqlParams, $filterSql, $categorySql, $imageSQL, $order);

    }
}