<?php

namespace Shopware\Bepado\Components\CategoryResolver;

use Shopware\Bepado\Components\CategoryResolver;

class DefaultCategoryResolver implements CategoryResolver
{
    /**
     * {@inheritdoc}
     */
    public function resolve(array $categories)
    {
        return array();
    }

    /**
     * {@inheritdoc}
     */
    public function generateTree(array $categories, $idPrefix = '')
    {
        return array();
    }
} 