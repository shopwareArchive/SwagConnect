<?php
/**
 * Shopware 5
 * Copyright (c) shopware AG
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

/**
 * Class Shopware_Controllers_Backend_Import
 */
class Shopware_Controllers_Backend_Import extends Shopware_Controllers_Backend_ExtJs
{
    private $categoryExtractor;

    public function getImportedProductCategoriesTreeAction()
    {
        $this->View()->assign(array(
            'success' => true,
            'data' => $this->getCategoryExtractor()->extractImportedCategories(),
        ));
    }

    private function getCategoryExtractor()
    {
        if (!$this->categoryExtractor) {
            $this->categoryExtractor = new \Shopware\Bepado\Components\CategoryExtractor(
                Shopware()->Models()->getRepository('Shopware\CustomModels\Bepado\Attribute'),
                new \Shopware\Bepado\Components\CategoryResolver\AutoCategoryResolver(
                    Shopware()->Models(),
                    Shopware()->Models()->getRepository('Shopware\Models\Category\Category')
                )
            );
        }

        return $this->categoryExtractor;
    }
} 