<?php

namespace Shopware\Bepado\Components\Translations;

interface TranslationServiceInterface
{
    public function get($topic, $value);
}