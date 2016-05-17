<?php

namespace ShopwarePlugins\Connect\Subscribers;


class Category extends BaseSubscriber
{
    private $db;

    public function __construct(\Zend_Db_Adapter_Pdo_Mysql $db)
    {
        parent::__construct();
        $this->db = $db;
    }

    public function getSubscribedEvents()
    {
        return array(
            'Enlight_Controller_Action_PostDispatch_Backend_Category' => 'extendBackendCategory',
        );
    }

    /**
     * @event Enlight_Controller_Action_PostDispatch_Backend_Article
     * @param \Enlight_Event_EventArgs $args
     */
    public function extendBackendCategory(\Enlight_Event_EventArgs $args)
    {
        /** @var $subject \Enlight_Controller_Action */
        $subject = $args->getSubject();
        $request = $subject->Request();

        if ($request->getActionName() === 'getList') {
            $subject->View()->data = $this->extendTreeNodes(
                $subject->View()->data
            );
        }
    }

    /**
     * @param array $nodes
     * @return array
     */
    private function extendTreeNodes(array $nodes)
    {
        $categoryIds = array_map(function ($node) {
            return (int)$node['id'];
        }, $nodes);

        $sql = 'SELECT categoryID
                FROM s_categories_attributes ca
                WHERE ca.categoryID IN (' . implode(', ', $categoryIds) . ')
                AND ca.connect_imported_category = 1';

        $rows = $this->db->fetchCol($sql);

        if (!$rows) {
            return $nodes;
        }

        $mappedCategories = array_flip($rows);

        foreach ($nodes as $index => $node) {
            if (isset($mappedCategories[$node['id']])) {
                $nodes[$index]['cls'] = 'sc-tree-node';
                $nodes[$index]['iconCls'] = 'sc-icon';
            }
        }

        return $nodes;
    }

}