/**
 * Shopware 4
 * Copyright © shopware AG
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
 * Shopware SwagBepado Plugin
 *
 * @category Shopware
 * @package Shopware\Plugins\SwagBepado
 * @copyright Copyright (c) shopware AG (http://www.shopware.de)
 */
//{namespace name=backend/bepado/view/main}
//{block name="backend/bepado/view/config/general/panel"}
Ext.define('Shopware.apps.Bepado.view.config.general.Panel', {
    extend: 'Ext.container.Container',
    alias: 'widget.bepado-config',

    border: false,
    layout: 'anchor',
    autoScroll: true,
    padding: '20 20 20 20',

    initComponent: function() {
        var me = this;

        Ext.applyIf(me, {
            items: me.createElements()
        });

        me.callParent(arguments);
    },

    /**
     * Creates the elements for the general configuration panel.
     * @return [Array]
     */
    createElements:function () {
        var descriptionFieldset;

        descriptionFieldset = Ext.create('Shopware.apps.Bepado.view.config.general.Description');

        return [ descriptionFieldset, { xtype: 'bepado-config-tabs' } ];
    }
});
//{/block}