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
//{block name="backend/connect/view/import/assign_category"}
Ext.define('Shopware.apps.Connect.view.import.AssignCategory', {
    extend: 'Ext.window.Window',
    alias: 'widget.connect-assign-category-window',

    layout: 'border',
    width: 500,
    height:'60%',
    modal: true,
    title: '{s name=import/options/assign_category_title}Assign category{/s}',

    initComponent: function() {
        var me = this;

        Ext.applyIf(me, {
            items: [
                {
                    xtype: 'treepanel',
                    region: 'center',
                    rootVisible: false,
                    root: {
                        id: 1,
                        expanded: true
                    },
                    store: 'mapping.Export',
                    columns: [{
                        xtype: 'treecolumn',
                        flex: 1,
                        dataIndex: 'text',
                        text: '{s name=import/options/shopware-category}Shopware Category{/s}'
                    }],
                    dockedItems: [ me.getButtons() ]
                }
            ]
        });

        me.callParent(arguments);
    },

    /**
     * Creates category tree buttons
     * @returns string
     */
    getButtons: function() {
        var me = this;

        return {
            dock: 'bottom',
            xtype: 'toolbar',
            items: ['->', {
                text: '{s name=import/options/save}Save{/s}',
                cls: 'primary',
                action: 'save'
            }]
        };
    }
});
//{/block}