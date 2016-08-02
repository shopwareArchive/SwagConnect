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

        $scQuery = $request->getParam('localCategoriesQuery', '');

        if ($request->getActionName() === 'getList') {

            if (trim($scQuery) !== "") {
                $parentId = $request->getParam('id', null);
                $subject->View()->data = $this->getCategoriesByQuery($scQuery, $parentId);
            }

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
        if (count($nodes) === 0) {
            return $nodes;
        }
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

    public function getCategoriesByQuery($query, $parentId)
    {

        $sql = 'SELECT *
                FROM s_categories ca
                WHERE ca.description LIKE ?';

        $rows = $this->db->fetchAll($sql, array('%' . $query . '%'));

        $parents = array();
        foreach ($rows as $row) {
            $maxParent = $this->getMaxRootCategories($row, $parentId);
            if (!in_array($maxParent, $parents) && $maxParent !== null) {
                $parents[] = $maxParent;
            }
        }

        $nodes = array();
        foreach ($parents as $parent) {
            $nodes[] = $this->createTreeNode(
                $parent['id'],
                $parent['description'],
                $parent['parent'],
                "",
                $this->isLeaf($parent['id']),
                true,
                true
            );
        }

        return $nodes;
    }

    public function createTreeNode($id, $name, $parentId, $class, $leaf, $allowDrag, $expanded)
    {
        return array(
            'id' => $id,
            'active' => true,
            'name' => $name,
            'position' => null,
            'parentId' => $parentId,
            'text' => $name,
            'cls' => $class,
            'leaf' => (bool)$leaf,
            'allowDrag' => $allowDrag,
            'expanded' => $expanded
        );
    }

    public function getMaxRootCategories($category, $parent)
    {

        if ($category['parent'] == 1 && $parent != 'NaN') {
            return null;
        }

        if ($category['parent'] == 1 && $parent == 'NaN') {
            return $category;
        }

        if ($category['parent'] == $parent) {
            return $category;
        }

        $sql = 'SELECT *
                FROM s_categories ca
                WHERE ca.id = ?';

        $parentCategory = $this->db->fetchRow($sql, array($category['parent']));

        return $this->getMaxRootCategories($parentCategory, $parent);
    }

    public function isLeaf($categoryId)
    {
        $sql = 'SELECT COUNT(id)
                FROM s_categories ca
                WHERE ca.parent = ?';

        $count = $this->db->fetchOne($sql, array($categoryId));

        return $count == 0;
    }
}