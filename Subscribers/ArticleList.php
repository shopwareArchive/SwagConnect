<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Subscribers;

/**
 * Implements a 'connect' filter for the article list
 *
 * Class ArticleList
 */
class ArticleList extends BaseSubscriber
{
    public function getSubscribedEvents()
    {
        return [
            'Shopware_Controllers_Backend_ArticleList_SQLParts' => 'onFilterArticle',
            'Enlight_Controller_Action_PostDispatch_Backend_ArticleList' => 'extentBackendArticleList',
        ];
    }

    /**
     * If the 'connect' filter is checked, only show products imported from connect
     *
     * @param \Enlight_Event_EventArgs $args
     */
    public function onFilterArticle(\Enlight_Event_EventArgs $args)
    {
        $subject = $args->getSubject();
        $filterBy = $subject->Request()->getParam('filterBy');

        list($sqlParams, $filterSql, $categorySql, $imageSQL, $order) = $args->getReturn();

        if ($filterBy == 'connect') {
            $imageSQL = '
                LEFT JOIN s_plugin_connect_items as connect_items
                ON connect_items.article_id = articles.id
            ';

            $filterSql .= ' AND connect_items.shop_id > 0 ';
        }

        return [$sqlParams, $filterSql, $categorySql, $imageSQL, $order];
    }

    /**
     * Extends the product list in the backend in order to have a special hint for connect products
     *
     * @event Enlight_Controller_Action_PostDispatch_Backend_ArticleList
     *
     * @param \Enlight_Event_EventArgs $args
     */
    public function extentBackendArticleList(\Enlight_Event_EventArgs $args)
    {
        /** @var $subject \Enlight_Controller_Action */
        $subject = $args->getSubject();
        $request = $subject->Request();

        switch ($request->getActionName()) {
            case 'load':
                $this->registerMyTemplateDir();
                $this->registerMySnippets();
                $subject->View()->extendsTemplate(
                    'backend/article_list/connect.js'
                );
                break;
            case 'list':
            case 'filter':
                $subject->View()->data = $this->markConnectProducts(
                    $subject->View()->data
                );
                break;
            case 'columnConfig':
                $subject->View()->data = $this->addConnectColumn(
                    $subject->View()->data
                );
                break;
            default:
                break;
        }
    }

    /**
     * Helper method which adds an additional 'connect' field to article objects in order to indicate
     * if they are connect articles or not
     *
     * @param $data
     *
     * @return mixed
     */
    private function markConnectProducts($data)
    {
        $articleIds = array_map(function ($row) {
            if ((int) $row['Article_id'] > 0) {
                return (int) $row['Article_id'];
            }

            return (int) $row['articleId'];
        }, $data);

        if (empty($articleIds)) {
            return $data;
        }

        $sql = 'SELECT article_id
                FROM s_plugin_connect_items
                WHERE article_id IN (' . implode(', ', $articleIds) . ')
                AND source_id IS NOT NULL
                AND shop_id IS NOT NULL';

        $connectArticleIds = array_map(function ($row) {
            return $row['article_id'];
        }, Shopware()->Db()->fetchAll($sql));

        foreach ($data as $idx => $row) {
            if ((int) $row['Article_id'] > 0) {
                $articleId = $row['Article_id'];
            } else {
                $articleId = $row['articleId'];
            }

            $data[$idx]['connect'] = in_array($articleId, $connectArticleIds);
        }

        return $data;
    }

    /**
     * Adds connect field to the ExtJS model for article_list
     * That was changed in SW5, because the model is dynamically created
     *
     * @param $data
     *
     * @return array
     */
    private function addConnectColumn($data)
    {
        $data[] = [
            'entity' => 'Article',
            'field' => 'connect',
            'type' => 'boolean',
            'alias' => 'connect',
            'allowInGrid' => true,
            'nullable' => false,
        ];

        return $data;
    }
}
