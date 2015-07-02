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
//{block name='backend/bepado/view/config/marketplace_attributes/mapping'}
Ext.define('Shopware.apps.Bepado.view.config.marketplaceAttributes.Mapping', {
    extend: 'Ext.container.Container',
    alias: 'widget.bepado-marketplace-attributes-mapping',

    layout: 'fit',

    snippets: {

    },

    initComponent: function() {
        var me = this;
        me.marketplaceAttributesStore = Ext.create('Shopware.apps.Bepado.store.config.MarketplaceAttributes').load();
        me.localProductAttributesStore = Ext.create('Shopware.apps.Bepado.store.config.LocalProductAttributes').load();



        Ext.applyIf(me, {
            items: [
                Ext.create('Ext.grid.Panel', {
                    alias: 'widget.bepado-marketplace-attributes-mapping-list',
                    store: me.localProductAttributesStore,
                    selModel: 'cellmodel',
                    plugins: [ me.createCellEditor() ],
                    columns: [{
                        header: '{s name=config/marketplace/shopware_attribute_header}Shopware attribute{/s}',
                        dataIndex: 'shopwareAttributeKey',
                        flex: 1
                    }, {
                        header: Ext.String.format('{s name=config/marketplace/bepado_attribute_header}[0]-Attribut{/s}', marketplaceName),
                        dataIndex: 'attributeKey',
                        flex: 1,
                        editor: {
                            xtype: 'combo',
                            store: me.marketplaceAttributesStore,
                            editable: false,
                            valueField: 'attributeKey',
                            displayField: 'attributeLabel'
                        },
                        renderer: function (value) {
                            var index = me.marketplaceAttributesStore.findExact('attributeKey', value);
                            if (index > -1) {
                                return me.marketplaceAttributesStore.getAt(index).get('attributeLabel');
                            }

                            return value;
                        }
                    }],
                    dockedItems: [ me.getButtons() ]
                })
            ]
        });

        me.callParent(arguments);
    },

    createCellEditor: function() {
        var me = this;

        me.cellEditor = Ext.create('Ext.grid.plugin.CellEditing', {
            clicksToMoveEditor: 1,
            autoCancel: true
        });

        return me.cellEditor;
    },

    getButtons: function() {
        var me = this;

        return {
            dock: 'bottom',
            xtype: 'toolbar',
            items: ['->', {
                text: '{s name=mapping/options/save}Save{/s}',
                cls: 'primary',
                action: 'save'
            }]
        };
    }
});
//{/block}