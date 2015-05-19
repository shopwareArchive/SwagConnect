<?php

namespace Shopware\Bepado\Components\Gateway;

use Bepado\SDK\Struct\Translation;

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

    /**
     * Inserts translation record for given configurator group
     *
     * @param $translation
     * @param $groupId
     * @param $shopId
     * @return void
     */
    public function addGroupTranslation($translation, $groupId, $shopId);

    /**
     * Inserts translation record for given configurator option
     *
     * @param string $translation
     * @param int $optionId
     * @param int $shopId
     * @return void
     */
    public function addOptionTranslation($translation, $optionId, $shopId);

    /**
     * Inserts translation record for given article
     *
     * @param Translation $translation
     * @param int $articleId
     * @param int $shopId
     * @return void
     */
    public function addArticleTranslation(Translation $translation, $articleId, $shopId);
}