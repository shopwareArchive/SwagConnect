<?php

namespace ShopwarePlugins\Connect\Components\Translations;

interface TranslationServiceInterface
{
    public function get($topic, $value);
}