<?php

namespace Shopware\Bepado\Components;

use Shopware\Components\Model\ModelManager;
use Shopware\Components\MultiEdit\Resource\Product;

class ImportService
{
    /**
     * @var ModelManager
     */
    private $manager;

    /**
     * @var \Shopware\Components\MultiEdit\Resource\Product
     */
    private $productResource;

    public function __construct(ModelManager $manager, Product $productResource)
    {
        $this->manager = $manager;
        $this->productResource = $productResource;
    }

    public function findBothArticlesType($categoryId = null, $limit = 10, $offset = 0)
    {
        return $this->productResource->filter($this->getAst($categoryId), $offset, $limit);
    }

    private function getAst($categoryId)
    {
        return $ast = array (
            array (
                'type' => 'nullaryOperators',
                'token' => 'ISMAIN',
            ),
            array (
                'type' => 'boolOperators',
                'token' => 'AND',
            ),
            array (
                'type' => 'subOperators',
                'token' => '(',
            ),
            array (
                'type' => 'attribute',
                'token' => 'CATEGORY.PATH',
            ),
            array (
                'type' => 'binaryOperators',
                'token' => '=',
            ),
            array (
                'type' => 'values',
                'token' => '"%|' . $categoryId . '|%"',
            ),
            array (
                'type' => 'boolOperators',
                'token' => 'OR',
            ),
            array (
                'type' => 'attribute',
                'token' => 'CATEGORY.ID',
            ),
            array (
                'type' => 'binaryOperators',
                'token' => '=',
            ),
            array (
                'type' => 'values',
                'token' => $categoryId,
            ),
            array (
                'type' => 'subOperators',
                'token' => ')',
            ),
        );
    }
} 