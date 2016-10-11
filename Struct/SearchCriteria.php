<?php

namespace ShopwarePlugins\Connect\Struct;


class SearchCriteria extends BaseStruct
{
    /**
     * @var string
     */
    public $search;

    /**
     * @var boolean
     */
    public $active;

    /**
     * @var string
     */
    public $exportStatus;

    /**
     * @var int
     */
    public $supplierId;

    /**
     * @var int
     */
    public $categoryId;

    /**
     * @var int
     */
    public $limit;

    /**
     * @var int
     */
    public $offset;

    /**
     * @var string
     */
    public $orderBy;

    /**
     * @var string
     */
    public $orderByDirection;

    public static function fromArray(array $query)
    {
        $struct = new self(
            array(
                'offset'    => isset($query['offset']) ? $query['offset'] : 0,
                'limit'     => isset($query['limit']) ? $query['limit'] : 20,
                'categoryId'  => isset($query['categoryId']) ? $query['categoryId'] : null,
                'supplierId'  => isset($query['supplierId']) ? $query['supplierId'] : null,
                'exportStatus'  => isset($query['exportStatus']) ? $query['exportStatus'] : null,
                'active'  => isset($query['active']) ? $query['active'] : null,
                'search'     => isset($query['search']) ? $query['search'] : null,
                'orderBy'   => isset($query['orderBy']) ? $query['orderBy'] : null,
                'orderByDirection'   => isset($query['orderByDirection']) ? $query['orderByDirection'] : 'asc',
            )
        );

        return $struct;
    }
}