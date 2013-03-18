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

/**
 * @category  Shopware
 * @package   Shopware\Plugins\SwagBepado
 * @copyright Copyright (c) 2013, shopware AG (http://www.shopware.de)
 * @author    Heiner Lohaus
 */
final class Shopware_Plugins_Frontend_SwagBepado_Bootstrap extends Shopware_Components_Plugin_Bootstrap
{
    /**
     * Returns the current version of the plugin.
     *
     * @return string
     */
    public function getVersion()
    {
        return '1.0.0';
    }

    /**
     * Returns a nice name for plugin manager list
     *
     * @return string
     */
    public function getLabel()
    {
        return 'Bepado';
    }

    /**
     * @return array
     */
    public function getInfo()
    {
        return array(
            'version' => $this->getVersion(),
            'label' => $this->getLabel(),
            //'description' => file_get_contents($this->Path() . 'info.txt'),
            'link' => 'http://www.shopware.de/',
        );
    }

	/**
	 * Install plugin method
	 *
	 * @return bool
	 */
	public function install()
	{	
        $this->createMyMenu();
        //$this->createMyForm();
        $this->createMyEvents();
        //$this->createMyTables();
        //$this->createMyAttributes();

	 	return true;
	}

    private function createMyAttributes()
    {
        $this->Application()->Models()->addAttribute(
            's_articles_attributes',
            'bepado',
            'shopId',
            'text'
        );

        $this->Application()->Models()->addAttribute(
            's_order_details_attributes',
            'bepado',
            'reservation_id',
            'text'
        );
        $this->Application()->Models()->addAttribute(
            's_order_basket_attributes',
            'bepado',
            'reservation_id',
            'text'
        );
        $this->Application()->Models()->generateAttributeModels(array(
            's_order_details_attributes',
            's_order_basket_attributes',
        ));
    }

    private function createMyMenu()
    {
        $parent = $this->Menu()->findOneBy(array('label' => 'Einstellungen'));
        $this->createMenuItem(array(
            'label' => $this->getLabel(),
            'controller' => 'Bepado',
            'action' => 'Index',
            'class' => 'sprite-ui-combo-box-edit',
            'active' => 1,
            'parent' => $parent
        ));
    }

    private function createMyForm()
    {
        $form = $this->Form();

        $form->setElement('text', 'imageSelectThumbnailSize', array(
            'label' => 'Bildauswahl > Thumbnail-Größe',
            'description' => 'Achtung: Die Thumbnail-Größe muss dafür im entsprechenden Album eingetragen sein.',
            'value' => '130x130',
            'required' => true
        ));
    }

    private function createMyEvents()
    {
        $this->subscribeEvent(
            'Enlight_Bootstrap_InitResource_BepadoSDK',
            'onInitResourceSDK'
        );

        $this->subscribeEvent(
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_Bepado',
            'onGetControllerPathBackend'
        );

        $this->subscribeEvent(
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_BepadoGateway',
            'onGetControllerPathGateway'
        );
    }

    private function createMyTables()
    {
        $queries = array("");

        foreach ($queries as $query) {
            Shopware()->Db()->exec($query);
        }
    }
	
	/**
	 * Uninstall plugin method
	 *
	 * @return bool
	 */
	public function uninstall()
	{
        return parent::uninstall();
	}

    private function removeMyAttributes()
    {
        try {
            $this->Application()->Models()->removeAttribute(
                's_order_details_attributes',
                'swag',
                'customizing'
            );
            $this->Application()->Models()->removeAttribute(
                's_order_basket_attributes',
                'swag',
                'customizing'
            );
            $this->Application()->Models()->generateAttributeModels(array(
                's_order_details_attributes',
                's_order_basket_attributes',
            ));
        } catch(Exception $e) { }

    }

    private function registerMyTemplateDir()
    {
        $this->Application()->Template()->addTemplateDir(
            $this->Path() . 'Views/', 'bepado'
        );
    }

    /**
     * @param   Enlight_Event_EventArgs $args
     * @return  Bepado\SDK\SDK
     */
    public function onInitResourceSDK(Enlight_Event_EventArgs $args)
    {
        $this->Application()->Loader()->registerNamespace(
            'Bepado',
            $this->Path() . 'Library/Bepado/'
        );
        $this->Application()->Loader()->registerNamespace(
            'Shopware\\Bepado',
            $this->Path() . 'Library/Shopware/Bepado/'
        );

        /** @var $connection PDO */
        $connection = $this->Application()->Db()->getConnection();
        $manager = $this->Application()->Models();
        $front = $this->Application()->Front();

        return new Bepado\SDK\SDK(
            '84c8f397-082f-48b7-b8de-b310dec89347',
            $front->Router()->assemble(array(
                'module' => 'backend',
                'controller' => 'bepado_gateway',
                'fullPath' => true
            )),
            new \Bepado\SDK\Gateway\PDO($connection),
            new \Shopware\Bepado\ProductToShop(
                $manager
            ),
            new \Shopware\Bepado\ProductFromShop(
                $manager
            )
        );
    }

    /**
     * @param   Enlight_Event_EventArgs $args
     * @return  string
     */
    public function onGetControllerPathBackend(Enlight_Event_EventArgs $args)
    {
        $this->registerMyTemplateDir();
        $this->Application()->Snippets()->addConfigDir(
            $this->Path() . 'Snippets/'
        );
        return $this->Path() . 'Controllers/Backend/Bepado.php';
    }

    /**
     * @param   Enlight_Event_EventArgs $args
     * @return  string
     */
    public function onGetControllerPathGateway(Enlight_Event_EventArgs $args)
    {
        return $this->Path() . 'Controllers/Backend/BepadoGateway.php';
    }
}