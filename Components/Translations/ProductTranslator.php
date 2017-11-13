<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
    ) {
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
        $exportLanguages = $exportLanguages ?: [];

        $translations = $this->productTranslationsGateway->getTranslations($productId, $exportLanguages);

        $result = [];
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
                [
                    'title' => isset($translation['title']) ? $translation['title'] : '',
                    'shortDescription' => isset($translation['shortDescription']) ? $translation['shortDescription'] : '',
                    'longDescription' => isset($translation['longDescription']) ? $translation['longDescription'] : '',
                    'additionalDescription' => isset($translation['additionalDescription']) ? $translation['additionalDescription'] : '',
                    'url' => $this->getUrlForProduct($sourceId, $shop->getId()),
                ]
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
        $exportLanguages = $exportLanguages ?: [];

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

            $groupTranslation = isset($groupTranslations[$shopId]) ? $groupTranslations[$shopId] : '';
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
        $exportLanguages = $exportLanguages ?: [];

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

            $optionTranslation = isset($optionTranslations[$shopId]) ? $optionTranslations[$shopId] : '';
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
        // currently we dont want to stop the other translations like description
        // even we dont have translated title
//        if (strlen($translation->title) === 0) {
//            throw new \Exception('Translation title cannot be empty string.');
//        }

        if (strlen($translation->url) === 0) {
            throw new \Exception('Translation url cannot be empty string.');
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
        $shopId = (int) $shopId;
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
}
