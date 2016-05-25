<?php

namespace ShopwarePlugins\Connect\Components;


class RandomStringGenerator
{
    /**
     * @param string $prefix
     * @return string
     */
    public function generate($prefix = "")
    {
        return uniqid($prefix);
    }
}