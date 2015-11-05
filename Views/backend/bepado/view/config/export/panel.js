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
 * Shopware SwagConnect Plugin
 *
 * @category Shopware
 * @package Shopware\Plugins\SwagConnect
 * @copyright Copyright (c) shopware AG (http://www.shopware.de)
 */
//{namespace name=backend/connect/view/main}
//{block name="backend/connect/view/config/export/panel"}
Ext.define('Shopware.apps.Connect.view.config.export.Panel', {
    extend: 'Ext.container.Container',
    alias: 'widget.connect-config-export',

    border: false,
    layout: 'border',
    autoScroll: true,

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
        var form;

        return [{
            xtype: 'connect-config-export-form'
        }];
    }
});
//{/block}