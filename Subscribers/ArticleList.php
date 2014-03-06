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
            'Enlight_Controller_Action_PostDispatch_Backend_ArticleList' => 'extentBackendArticleList'
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

    /**
     * Extends the product list in the backend in order to have a special hint for bepado products
     *
     * @event Enlight_Controller_Action_PostDispatch_Backend_ArticleList
     * @param \Enlight_Event_EventArgs $args
     */
    public function extentBackendArticleList(\Enlight_Event_EventArgs $args)
    {
        /** @var $subject \Enlight_Controller_Action */
        $subject = $args->getSubject();
        $request = $subject->Request();

        switch($request->getActionName()) {
            case 'load':
                $this->registerMyTemplateDir();
                $this->registerMySnippets();
                $subject->View()->extendsTemplate(
                    'backend/article_list/bepado.js'
                );
                break;
            case 'list':
                $subject->View()->data = $this->markBepadoProducts(
                    $subject->View()->data
                );
                break;
            default:
                break;
        }
    }

    /**
     * Helper method which adds an additional 'bepado' field to article objects in order to indicate
     * if they are bepado articles or not
     *
     * @param $data
     * @return mixed
     */
    private function markBepadoProducts($data)
    {
        $articleIds = array_map(function ($row) {
            return (int)$row['articleId'];
        }, $data);

        if (empty($articleIds)) {
            return $data;
        }

        $sql = 'SELECT article_id FROM s_plugin_bepado_items WHERE article_id IN (' . implode(', ', $articleIds) . ') AND source_id IS NOT NULL';
        $bepadoArticleIds = array_map(function ($row) {
            return $row['article_id'];
        }, Shopware()->Db()->fetchAll($sql));

        foreach($data as $idx => $row) {
            $data[$idx]['bepado'] = in_array($row['articleId'], $bepadoArticleIds);
        }

        return $data;
    }

}