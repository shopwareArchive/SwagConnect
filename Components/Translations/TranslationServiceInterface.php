<?php

namespace Shopware\Connect\Components\Translations;

interface TranslationServiceInterface
{
    public function get($topic, $value);
}