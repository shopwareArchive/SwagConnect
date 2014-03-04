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

use Shopware\Bepado\Components\Config;

/**
 * @category  Shopware
 * @package   Shopware\Plugins\SwagBepado
 * @copyright Copyright (c) 2013, shopware AG (http://www.shopware.de)
 */
class Shopware_Controllers_Backend_BepadoConfig extends Shopware_Controllers_Backend_ExtJs
{

    /** @var  \Shopware\Bepado\Components\Config */
    private $configComponent;

    /**
     * The getGeneralAction function is an ExtJs event listener method of the
     * bepado module. The function is used to load store
     * required in the general config form.
     * @return string
     */
    public function getGeneralAction()
    {
        $generalConfig = $this->getConfigComponent()->getGeneralConfigArrays();

        $this->View()->assign(array(
            'success' => true,
            'data' => $generalConfig
        ));
    }

    /**
     * The saveGeneralAction function is an ExtJs event listener method of the
     * bepado module. The function is used to load store
     * required in the general config form.
     * @return string
     */
    public function saveGeneralAction()
    {
        $data = $this->Request()->getParam('data');
        $data = !isset($data[0]) ? array($data) : $data;

        $this->getConfigComponent()->setGeneralConfigsArrays($data);

        $this->View()->assign(array(
                'success' => true
        ));
    }

    /**
     * Helper function to get access on the Config component
     *
     * @return \Shopware\Bepado\Components\Config
     */
    private function getConfigComponent()
    {
        if ($this->configComponent === null) {
            $modelsManager = Shopware()->Models();
            $this->configComponent = new Config($modelsManager);
        }

        return $this->configComponent;
    }

} 