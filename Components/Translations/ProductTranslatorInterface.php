<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Components\Translations;

use Shopware\Connect\Struct\Translation;

interface ProductTranslatorInterface
{
    /**
     * Returns product translations as array
     * with sdk translation objects
     *
     * @param int    $productId
     * @param string $sourceId
     *
     * @return array
     */
    public function translate($productId, $sourceId);

    /**
     * @param int                                    $groupId
     * @param string                                 $groupName
     * @param \Shopware\Connect\Struct\Translation[] $translations
     *
     * @return \Shopware\Connect\Struct\Translation[]
     */
    public function translateConfiguratorGroup($groupId, $groupName, $translations);

    /**
     * @param int                                    $optionId
     * @param string                                 $optionName
     * @param \Shopware\Connect\Struct\Translation[] $translations
     *
     * @return \Shopware\Connect\Struct\Translation[]
     */
    public function translateConfiguratorOption($optionId, $optionName, $translations);

    /**
     * @param Translation $translation
     * @param int         $optionsCount
     *
     * @throws \Exception
     *
     * @return bool|true
     */
    public function validate(Translation $translation, $optionsCount);
}
