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

namespace Shopware\Bepado\Components\Translations;

use Shopware\Bepado\Components\Config;
use Shopware\Bepado\Components\Gateway\ProductTranslationsGateway;
use Shopware\Components\Model\ModelManager;
use Bepado\SDK\Struct\Translation;

class ProductTranslator implements ProductTranslatorInterface
{
    /**
     * @var \Shopware\Bepado\Components\Config
     */
    private $config;

    /**
     * @var \Shopware\Bepado\Components\Gateway\ProductTranslationsGateway
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
     * Returns product translations as array
     * with sdk translation objects
     *
     * @param int $productId
     * @param string $sourceId
     * @return array
     */
    public function translate($productId, $sourceId)
    {
        $exportLanguages = $this->config->getConfig('exportLanguages');
        $exportLanguages = $exportLanguages ?: array();
        $translations = $this->productTranslationsGateway->getTranslations($productId, $exportLanguages);

        $result = array();
        foreach ($translations as $localeId => $translation) {
            /** @var \Shopware\Models\Shop\Locale $locale */
            $locale = $this->getLocaleRepository()->find($localeId);
            if (!$locale) {
                continue;
            }

            $localeCode = explode('_', $locale->getLocale());
            if (count($localeCode) === 0) {
                continue;
            }

            /** @var \Shopware\Models\Shop\Shop $shop */
            $shop = $this->getShopRepository()->findOneBy(array('locale' => $localeId));
            if (!$shop) {
                continue;
            }

            $result[$localeCode[0]] = new Translation(
                array(
                    'title' => $translation['title'],
                    'shortDescription' => $translation['shortDescription'],
                    'longDescription' => $translation['longDescription'],
                    'url' => $this->getUrlForProduct($sourceId, $shop->getId()),
                )
            );
        }

        return $result;
    }

    public function getUrlForProduct($productId, $shopId = null)
    {
        $shopId = (int)$shopId;
        $url = $this->baseProductUrl . $productId;
        if ($shopId > 0) {
            $url = $url . '&shId=' . $shopId;
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