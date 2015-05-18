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

    /**
     * Returns translation for variant configurator group.
     * If translation does not exist, it will return null
     *
     * @param int $groupId
     * @param int $shopId
     * @return string | null
     */
    public function getConfiguratorGroupTranslation($groupId, $shopId);

    /**
     * Returns translations for variant configurator groups.
     * Returned array contains key value pairs,
     * shopId as key and translation as value.
     * 2 => 'Size'
     *
     * @param int $groupId
     * @param array $shopIds
     * @return array
     */
    public function getConfiguratorGroupTranslations($groupId, $shopIds);

    /**
     * Returns translation for variant option.
     * If translation does not exist, it will return null
     *
     * @param int $optionId
     * @param int $shopId
     * @return string | null
     */
    public function getConfiguratorOptionTranslation($optionId, $shopId);

    /**
     * Returns translations for variant options.
     * Returned array contains key value pairs,
     *
     * @param int $optionId
     * @param array $shopIds
     * @return array
     */
    public function getConfiguratorOptionTranslations($optionId, $shopIds);
}