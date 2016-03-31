<?php

namespace ShopwarePlugins\Connect\Components\ProductStream;

use ShopwarePlugins\Connect\Components\Struct;


class ProductStreamsAssignments extends Struct
{
    /**
     * @var array
     */
    public $assignments;

    /**
     * @param $articleId
     * @return array
     */
    public function getStreamsByArticleId($articleId)
    {
        return $this->assignments[$articleId];
    }

    /**
     * @return array
     */
    public function getArticleIds()
    {
        return array_keys($this->assignments);
    }

}