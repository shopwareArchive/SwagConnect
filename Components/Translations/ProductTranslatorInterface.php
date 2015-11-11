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

use Shopware\Connect\Struct\Translation;

interface ProductTranslatorInterface
{
    /**
     * Returns product translations as array
     * with sdk translation objects
     *
     * @param int $productId
     * @param string $sourceId
     * @return array
     */
    public function translate($productId, $sourceId);

    /**
     * @param int $groupId
     * @param string $groupName
     * @param \Shopware\Connect\Struct\Translation[] $translations
     * @return \Shopware\Connect\Struct\Translation[]
     */
    public function translateConfiguratorGroup($groupId, $groupName, $translations);

    /**
     * @param int $optionId
     * @param string $optionName
     * @param \Shopware\Connect\Struct\Translation[] $translations
     * @return \Shopware\Connect\Struct\Translation[]
     */
    public function translateConfiguratorOption($optionId, $optionName, $translations);

    /**
     * @param Translation $translation
     * @param int $optionsCount
     * @return bool|true
     * @throws \Exception
     */
    public function validate(Translation $translation, $optionsCount);
}