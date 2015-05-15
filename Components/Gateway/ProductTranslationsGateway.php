<?php

namespace Shopware\Bepado\Components\Gateway;

interface ProductTranslationsGateway
{
    /**
     * Returns array with title, shortDescription
     * and longDescription for given articleId and languageId
     *
     * @param int $articleId
     * @param int $languageId
     * @return array | null
     */
    public function getSingleTranslation($articleId, $languageId);

    public function getTranslations($articleId, $languageIds);
} 