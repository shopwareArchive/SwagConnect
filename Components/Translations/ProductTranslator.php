<?php
/**
 * Shopware 4.0
 * Copyright © 2013 shopware AG
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 */

namespace ShopwarePlugins\Connect\Components\Translations;

use ShopwarePlugins\Connect\Components\Config;
use ShopwarePlugins\Connect\Components\Gateway\ProductTranslationsGateway;
use Shopware\Components\Model\ModelManager;
use Shopware\Connect\Struct\Translation;

class ProductTranslator implements ProductTranslatorInterface
{
    /**
     * @var \ShopwarePlugins\Connect\Components\Config
     */
    private $config;

    /**
     * @var \ShopwarePlugins\Connect\Components\Gateway\ProductTranslationsGateway
     */
    private $productTranslationsGateway;

    /**
     * @var \Shopware\Components\Model\ModelManager
     */
    private $manager;

    /**
     * @var string
     */
    private $baseProductUrl;

    private $shopRepository;

    private $localeRepository;

    public function __construct(
        Config $config,
        ProductTranslationsGateway $productTranslationsGateway,
        ModelManager $manager,
        $baseProductUrl
    )
    {
        $this->config = $config;
        $this->productTranslationsGateway = $productTranslationsGateway;
        $this->manager = $manager;
        $this->baseProductUrl = $baseProductUrl;
    }

    /**
     * {@inheritdoc}
     */
    public function translate($productId, $sourceId)
    {
        $exportLanguages = $this->config->getConfig('exportLanguages');
        $exportLanguages = $exportLanguages ?: array();

        $translations = $this->productTranslationsGateway->getTranslations($productId, $exportLanguages);

        $result = array();
        foreach ($translations as $shopId => $translation) {
            /** @var \Shopware\Models\Shop\Shop $shop */
            $shop = $this->getShopRepository()->find($shopId);
            if (!$shop) {
                continue;
            }

            /** @var \Shopware\Models\Shop\Locale $locale */
            $locale = $shop->getLocale();
            if (strlen($locale->getLocale()) === 0) {
                continue;
            }

            $localeCode = explode('_', $locale->getLocale());

            if (count($localeCode) === 0) {
                continue;
            }

            $result[$localeCode[0]] = new Translation(
                array(
                    'title' => $translation['title'],
                    'shortDescription' => $translation['shortDescription'],
                    'longDescription' => $translation['longDescription'],
                    'additionalDescription' => $translation['additionalDescription'],
                    'url' => $this->getUrlForProduct($sourceId, $shop->getId()),
                )
            );
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function translateConfiguratorGroup($groupId, $groupName, $translations)
    {
        // exportLanguages actually is shop ids
        $exportLanguages = $this->config->getConfig('exportLanguages');
        $exportLanguages = $exportLanguages ?: array();

        $groupTranslations = $this->productTranslationsGateway->getConfiguratorGroupTranslations($groupId, $exportLanguages);
        foreach ($exportLanguages as $shopId) {
            if ($shopId === 1) {
                continue;
            }
            /** @var \Shopware\Models\Shop\Shop $shop */
            $shop = $this->getShopRepository()->find($shopId);
            if (!$shop) {
                continue;
            }

            /** @var \Shopware\Models\Shop\Locale $locale */
            $locale = $shop->getLocale();
            if (!$locale) {
                continue;
            }

            $localeCode = explode('_', $locale->getLocale());
            if (count($localeCode) === 0) {
                continue;
            }

            $groupTranslation = isset($groupTranslations[$shopId]) ? $groupTranslations[$shopId] : $groupName;
            if (!array_key_exists($localeCode[0], $translations)) {
                continue;
            }
            $translationStruct = $translations[$localeCode[0]];
            if (!$translationStruct instanceof Translation) {
                continue;
            }

            $translationStruct->variantLabels[$groupName] = $groupTranslation;
        }

        return $translations;
    }

    /**
     * {@inheritdoc}
     */
    public function translateConfiguratorOption($optionId, $optionName, $translations)
    {
        $exportLanguages = $this->config->getConfig('exportLanguages');
        $exportLanguages = $exportLanguages ?: array();

        $optionTranslations = $this->productTranslationsGateway->getConfiguratorOptionTranslations($optionId, $exportLanguages);
        foreach ($exportLanguages as $shopId) {
            if ($shopId === 1) {
                continue;
            }
            /** @var \Shopware\Models\Shop\Shop $shop */
            $shop = $this->getShopRepository()->find($shopId);
            /** @var \Shopware\Models\Shop\Locale $locale */
            $locale = $shop->getLocale();
            if (!$locale) {
                continue;
            }

            $localeCode = explode('_', $locale->getLocale());
            if (count($localeCode) === 0) {
                continue;
            }

            $optionTranslation = isset($optionTranslations[$shopId]) ? $optionTranslations[$shopId] : $optionName;
            $translationStruct = $translations[$localeCode[0]];
            if (!$translationStruct instanceof Translation) {
                continue;
            }

            $translationStruct->variantValues[$optionName] = $optionTranslation;
        }

        return $translations;
    }

    /**
     * {@inheritdoc}
     */
    public function validate(Translation $translation, $optionsCount)
    {
        if (strlen($translation->title) === 0) {
            throw new \Exception('Translation title cannot be empty string.');
        }

        if (strlen($translation->url) === 0) {
            throw new \Exception('Translation title cannot be empty string.');
        }

        if (count($translation->variantLabels) != $optionsCount) {
            throw new \Exception('variantLabels property has not correct items count.');
        }

        if (count($translation->variantValues) != $optionsCount) {
            throw new \Exception('variantValues property has not correct items count.');
        }

        return true;
    }

    public function getUrlForProduct($productId, $shopId = null)
    {
        $shopId = (int)$shopId;
        $url = $this->baseProductUrl . $productId;
        if ($shopId > 0) {
            $url = $url . '/shId/' . $shopId;
        }

        return $url;
    }

    private function getShopRepository()
    {
        if (!$this->shopRepository) {
            $this->shopRepository = $this->manager->getRepository('Shopware\Models\Shop\Shop');
        }

        return $this->shopRepository;
    }

    private function getLocaleRepository()
    {
        if (!$this->localeRepository) {
            $this->localeRepository = $this->manager->getRepository('Shopware\Models\Shop\Locale');
        }

        return $this->localeRepository;
    }
} 