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
//{block name="backend/bepado/view/config/export/form"}
Ext.define('Shopware.apps.Bepado.view.config.export.Form', {
    extend: 'Ext.form.Panel',
    alias: 'widget.bepado-config-export-form',

    border: false,
    layout: 'anchor',
    autoScroll: true,
    region: 'center',
    bodyPadding: 10,

    /**
     * Contains the field set defaults.
     */
    defaults: {
        labelWidth: 200,
        anchor: '100%'
    },

    snippets: {
        save: '{s name=config/save}Save{/s}',
        cancel: '{s name=config/cancel}Cancel{/s}',
        productDescriptionFieldLabel: '{s name=config/export/product_description_field_label}Product description field{/s}',
        autoProductSync: '{s name=config/export/auto_product_sync_label}Automatically sync changed products to bepado{/s}',
        autoPlayedChanges: '{s name=config/export/changes_auto_played_label}Will autmatically sync changed bepado products to the bepado platform{/s}',
        emptyText: '{s name=config/export/empty_text_combo}Please choose{/s}'
    },

    initComponent: function () {
        var me = this;

        me.items = me.createElements();
        me.dockedItems = [
            {
                xtype: 'toolbar',
                dock: 'bottom',
                ui: 'shopware-ui',
                cls: 'shopware-toolbar',
                items: me.getFormButtons()
            }
        ];

        me.exportConfigStore = Ext.create('Shopware.apps.Bepado.store.config.Export').load({
            callback: function() {
                me.populateForm();
            }
        });

        me.callParent(arguments);
    },

    /**
     * Returns form buttons, save and cancel
     * @returns Array
     */
    getFormButtons: function () {
        var me = this,
            buttons = ['->'];

        var saveButton = Ext.create('Ext.button.Button', {
            text: me.snippets.save,
            action: 'save-export-config',
            cls: 'primary'
        });

        var cancelButton = Ext.create('Ext.button.Button', {
            text: me.snippets.cancel,
            handler: function (btn) {
                btn.up('window').close();
            }
        });

        buttons.push(cancelButton);
        buttons.push(saveButton);

        return buttons;
    },

    /**
     * Creates the field set items
     * @return Array
     */
    createElements: function () {
        var me = this;

        var container = Ext.create('Ext.container.Container', {
            columnWidth: 1,
            padding: '0 0 20 0',
            layout: 'fit',
            border: false,
            items: [
                {
                    xtype: 'combobox',
                    fieldLabel: me.snippets.productDescriptionFieldLabel,
                    emptyText: me.snippets.emptyText,
                    name: 'alternateDescriptionField',
                    store: new Ext.data.SimpleStore({
                        fields: [ 'value', 'text' ],
                        data: [
                            ['attribute.bepadoProductDescription', 'attribute.bepadoProductDescription'],
                            ['a.description', 'Artikel-Kurzbeschreibung'],
                            ['a.descriptionLong', 'Artikel-Langbeschreibung']
                        ]
                    }),
                    queryMode: 'local',
                    displayField: 'text',
                    valueField: 'value',
                    editable: false,
                    labelWidth: me.defaults.labelWidth
                }, {
                    xtype: 'base-element-selecttree',
                    allowBlank: true,
                    store: 'mapping.BepadoCategoriesExport',
                    name: 'defaultExportCategory',
                    labelWidth: me.defaults.labelWidth,
                    fieldLabel: 'Default Export category',
                    displayField: 'name',
                    valueField: 'id'
                }, {
                    xtype: 'fieldcontainer',
                    fieldLabel: me.snippets.autoProductSync,
                    defaultType: 'checkboxfield',
                    labelWidth: me.defaults.labelWidth,
                    items: [
                        {
                            boxLabel: me.snippets.autoPlayedChanges,
                            name: 'autoUpdateProducts',
                            inputValue: 1,
                            uncheckedValue: 0
                        }
                    ]
                }
            ]
        });

        return [
            {
                xtype: 'bepado-config-export-description'
            },
            container,
            {
                xtype: 'fieldset',
                title: '{s name =config/export/priceConfiguration}Price configuration{/s}',
                items: [
                    {
                        xtype: 'label',
                        html: '{s name=config/export/label/priceDescription}Here you can configure the prices that will be exported as your product price. You can configure the  »customer« price and the »merchant« price. Foreach each of these prices you can configure from which price group the value should be read and which price field should be used.<br><br>{/s}'
                    },
                    me.createPriceField('price'),
                    me.createPriceField('purchasePrice'),
                ]

            }

        ];
    },

    /**
     * Creates a price config fieldcontainer for price or purchasePrice
     *
     * @return Object
     */
    createPriceField: function (type) {
        var me = this,
            fieldLabel,
            dataIndexCustomerGroup,
            dataIndexField,
            helpText;

        if (type == 'price') {
            fieldLabel = '{s name=config/price/price}Price{/s}';
            dataIndexCustomerGroup = 'priceGroupForPriceExport';
            dataIndexField = 'priceFieldForPriceExport';
            helpText = '{s name=config/export/help/price}Configure, which price field of which customer group should be exported as the product\'s end user price{/s}';
        } else if (type == 'purchasePrice') {
            fieldLabel = '{s name=config/price/purchasePrice}PurchasePrice{/s}';
            dataIndexCustomerGroup = 'priceGroupForPurchasePriceExport';
            dataIndexField = 'priceFieldForPurchasePriceExport';
            helpText = '{s name=config/export/help/purchasePrice}Configure, which price field of which customer group should be exported as the product\'s merchant price{/s}';
        } else {
            return { };
        }

        return {
            fieldLabel: fieldLabel,
            xtype: 'fieldcontainer',
            layout: 'hbox',
            items: [
                {
                    xtype: 'combobox',
                    queryMode: 'remote',
                    editable: false,
                    name: dataIndexCustomerGroup,
                    allowBlank: false,
                    displayField: 'name',
                    valueField: 'key',
                    store: Ext.create('Shopware.apps.Bepado.store.config.CustomerGroup', { }).load(),
                    supportText: '{s name=config/export/support/customer}customer group{/s}'
                },
                {
                    xtype: 'combobox',
                    name: dataIndexField,
                    store: Ext.create('Ext.data.Store', {
                        fields: ['field'],
                        data: me.getPriceData()
                    }),
                    queryMode: 'local',
                    editable: false,
                    allowBlank: false,
                    displayField: 'field',
                    valueField: 'field',
                    helpText: helpText,
                    supportText: '{s name=config/export/support/price}price field{/s}'

                }
            ]
        };
    },

    /**
     * Returns allowed price columns
     *
     * @returns Array
     */
    getPriceData: function () {
        var me = this,
            columns = [
                { field: 'basePrice' },
                { field: 'price' },
                { field: 'pseudoPrice' }
            ];

        return columns;
    },

    /**
     * Populate export config form
     */
    populateForm: function () {
        var me = this,
            record = me.exportConfigStore.getAt(0);

        if (!record) {
            record = Ext.create('Shopware.apps.Bepado.model.config.Export');
        }

        me.loadRecord(record);
    }
});
//{/block}

