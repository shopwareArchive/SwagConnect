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
//{block name="backend/connect/view/config/export/form"}
Ext.define('Shopware.apps.Connect.view.config.export.Form', {
    extend: 'Ext.form.Panel',
    alias: 'widget.connect-config-export-form',

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

    isExported: false,

    snippets: {
        save: '{s name=config/save}Save{/s}',
        cancel: '{s name=config/cancel}Cancel{/s}',
        productSettingsLegend: '{s name=config/export/product_settings_legend}Product settings{/s}',
        productDescriptionLegend: '{s name=config/export/product_description_legend}Product description{/s}',
        productDescriptionFieldLabel: '{s name=config/export/product_description_field_label}Product description field{/s}',
        productDescriptionFieldHelp: Ext.String.format('{s name=config/export/product_description_field_help}Wählen Sie aus, welches Textfeld als Produkt-Beschreibung zu [0] exportiert werden soll und anderen Händlern zur Verfügung gestellt wird.{/s}',marketplaceName),
        longDescription: '{s name=config/export/product_long_description}Product long description{/s}',
        shortDescription: '{s name=config/export/product_short_description}Product short description{/s}',
        additionalDescription: '{s name=config/export/product_additional_description}Connect description{/s}',
        autoProductSync: '{s name=config/export/auto_product_sync_label}Geänderte Produkte automatisch synchronisieren{/s}',
        autoPlayedChanges: Ext.String.format('{s name=config/export/changes_auto_played_label}Änderungen automatisch mit [0] synchronsieren{/s}', marketplaceName),
        emptyText: '{s name=config/export/empty_text_combo}Please choose{/s}',
        priceConfiguration: '{s name=config/export/priceConfiguration}Preiskonfiguration{/s}',
        priceConfigurationDescription: Ext.String.format('{s name=config/export/label/export_price_description}Hier bestimmen Sie die Preise, die Sie zu [0] exportieren möchten. Alle Preise werden netto exportiert und können individuell mit Auf-und Abschlägen bearbeitet werden.<br><br>{/s}', marketplaceName),
        priceMode: '{s name=config/config/price/priceMode}Endkunden-VK{/s}',
        priceModeDescription: Ext.String.format('{s name=config/export/label/price_mode_description}Preiskalkulation auf [0]: <div class="ul-disc-type-holder"><ul><li>Exportieren Sie zum Beispiel nur einen Endkunden VK, können Sie über einen Abschlag einen Händlereinkaufspreis bestimmen.</li><li>Exportieren Sie einen Listenverkaufspreis, können Sie mit auf oder Abschlägen einen Händlereinkaufspreis definieren und optional eine unverbindliche Preisempfehlung für den Verkaufspreis definieren.</li><li>Exportieren Sie einen Endkunden Verkaufspreis und einen Listenverkaufspreis, können Sie optional Preise auf [0] bearbeiten.</li></ul></div>{/s}', marketplaceName),
        purchasePriceMode: '{s name=config/price/purchasePriceMode}Listenverkaufspreis-VK{/s}',
        exportLanguagesTitle: '{s name=config/export/exportLanguagesTitle}Sprachen{/s}',
        exportLanguagesLabel: '{s name=config/export/exportLanguagesLabel}Sprachauswahl{/s}',
        exportLanguagesHelpText: Ext.String.format('{s name=config/export/exportLanguagesHelpText}Hier legen Sie fest, welche Sprachen für Ihren Export zu [0] verwendet werden sollen. Wenn Sie die Produkte inkl. Übersetzung exportieren möchten, können Sie mehrere Sprachen auswählen. Wenn Sie dieses Feld leer lassen, wird automatisch die standard- Sprache Ihres Shops verwendet.{/s}', marketplaceName),
        price: '{s name=export/price/price}Price{/s}',
        pseudoPrice: '{s name=export/price/pseudo_price}Pseudo price{/s}',
        purchasePrice: '{s name=export/price/purchase_price}Purchase price{/s}',
        yes: '{s name=connect/yes}Ja{/s}',
        no: '{s name=connect/no}Nein{/s}',
        edit: '{s name=connect/edit}Edit{/s}',
        excludeInactiveProducts: '{s name=config/export/exclude_inactive_products}Exclude inactive products from export{/s}',
        purchasePriceHint: '{s name=export/price/purchase_price_hint}You have the option to export your purchase prices to connect and edit it there. They are only visible for you.{/s}'
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

        me.exportConfigStore = Ext.create('Shopware.apps.Connect.store.config.Export').load({
            callback: function() {
                me.populateForm();
            }
        });

        me.loadPriceStores();

        me.callParent(arguments);
    },

    registerEvents: function() {
        this.addEvents('rejectPriceConfigChanges', 'collectPriceParams');
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
            handler: function (btn) {
                var form = btn.up('form');
                var model = form.getRecord();

                form.getForm().updateRecord(model);

                if (me.isExported == false) {
                    model.data.exportPriceMode = [];
                    if (purchasePriceInDetail == false) {
                        me.fireEvent('collectPriceParams', me.purchasePriceTabPanel, 'purchasePrice', model.data);
                    } else if (purchasePriceInDetail == true && me.purchasePriceTabPanel.getValue()) {
                        model.data.exportPriceMode.push('purchasePrice');
                    }

                    me.fireEvent('collectPriceParams', me.priceTabPanel, 'price', model.data);
                }

                model.data.descriptionField = [];
                me.articleDescriptions.forEach(function(value) {
                    if (value.getValue()) {
                        model.data.descriptionField.push(value.getName());
                    }
                });

                me.fireEvent('saveExportSettings', model.data, btn);
            },
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

    createPriceContainer: function (item, title) {
        return Ext.create('Ext.form.FieldSet', {
            columnWidth: 1,
            title: title,
            layout: 'anchor',
            width: '90%',
            items: [
                item
            ]
        });
    },

    /**
     * Creates the elements for the description field set.
     * @return array Contains all Ext.form.Fields for the description field set
     */
    createPriceTab: function () {
        var me = this, tabs = [];

        me.customerGroupStore.each(function (customerGroup) {
            if (customerGroup.get('mode') === false) {
                var tab = me.createPriceGrid(customerGroup);
                tabs.push(tab);
            }
        });

        return Ext.create('Ext.tab.Panel', {
            activeTab: 0,
            layout: 'card',
            items: tabs
        });
    },

    /**
     * Creates a grid
     *
     * @param customerGroup
     * @return Ext.grid.Panel
     */
    createPriceGrid: function (customerGroup) {
        var me = this;

        return Ext.create('Ext.grid.Panel', {
            height: 100,
            sortableColumns: false,
            defaults: {
                align: 'right',
                flex: 2
            },
            plugins: [{
                ptype: 'cellediting',
                clicksToEdit: 1
            }],
            title: customerGroup.get('name'),
            store: Ext.create('Shopware.apps.Connect.store.config.PriceGroup'),
            customerGroup: customerGroup,
            columns: me.createGridColumns()
        });
    },

    createGridColumns: function() {
        var me = this;
        var columns = [
            {
                header: '',
                flex: 2
            }, {
                header: me.snippets.price,
                dataIndex: 'price',
                columnType: 'price',
                xtype: 'checkboxcolumn',
                flex: 3,
                listeners: {
                    beforecheckchange: function(column, view, cell, recordIndex, cellIndex){
                        me.fireEvent('rejectPriceConfigChanges', column, view, cell, recordIndex, cellIndex);
                    }
                }
            }, {
                header: me.snippets.pseudoPrice,
                dataIndex: 'pseudoPrice',
                columnType: 'pseudoPrice',
                xtype: 'checkboxcolumn',
                flex: 3,
                listeners: {
                    beforecheckchange: function(column, view, cell, recordIndex, cellIndex){
                        me.fireEvent('rejectPriceConfigChanges', column, view, cell, recordIndex, cellIndex);
                    }
                }
            }
        ];

        if (purchasePriceInDetail == false) {
            var basePrice = {
                header: me.snippets.purchasePrice,
                dataIndex: 'basePrice',
                columnType: 'basePrice',
                xtype: 'checkboxcolumn',
                flex: 3,
                listeners: {
                    beforecheckchange: function(column, view, cell, recordIndex, cellIndex){
                        me.fireEvent('rejectPriceConfigChanges', column, view, cell, recordIndex, cellIndex);
                    }
                }
            };

            columns.push(basePrice);
        }

        return columns;
    },

    loadPriceStores: function() {
        var me = this;

        if (purchasePriceInDetail == false) {
            me.purchasePriceTabPanel.items.each(function(tab){
                tab.getStore().load({
                    params: {
                        'customerGroup': tab.customerGroup.get('key'),
                        'priceExportMode': 'purchasePrice'
                    }
                });
            });
        }

        me.priceTabPanel.items.each(function(tab){
            tab.getStore().load({
                params: {
                    'customerGroup': tab.customerGroup.get('key'),
                    'priceExportMode': 'price'
                }
            });
        });
    },

    /**
     * Creates the field set items
     * @return Array
     */
    createElements: function () {
        var me = this;
        var container = me.createProductContainer();

        if (purchasePriceInDetail == false) {
            me.purchasePriceTabPanel = me.createPriceTab();
        } else {
            me.purchasePriceTabPanel = Ext.create('Ext.form.field.Checkbox', {
                boxLabel: me.snippets.purchasePrice,
                name: 'exportPriceMode',
                inputValue: 'purchasePrice',
                helpText: me.snippets.purchasePriceHint
            });
        }

        me.priceTabPanel = me.createPriceTab();

        me.languagesExportFieldset = Ext.create('Ext.form.FieldSet', {
            title: me.snippets.exportLanguagesTitle,
            items: [
                {
                    xtype: 'label',
                    html: me.snippets.exportLanguagesHelpText
                },
                me.createLanguagesCombo()
            ]
        });

        Ext.getStore('export.List').load();

        return [
            me.createPriceContainer(me.purchasePriceTabPanel, me.snippets.purchasePriceMode),
            me.createPriceContainer(me.priceTabPanel, me.snippets.priceMode),
            container,
            me.createProductDescription(),
            me.languagesExportFieldset
        ];
    },

    createProductDescription: function () {
        var me = this;

        me.articleDescriptions = [
            Ext.create('Ext.form.field.Checkbox', {
                xtype: 'checkbox',
                labelWidth: 150,
                fieldLabel: me.snippets.longDescription,
                name: 'longDescriptionField',
                inputValue: 1,
                checked: true,
                uncheckedValue: 0
            }), Ext.create('Ext.form.field.Checkbox', {
                xtype: 'checkbox',
                labelWidth: 150,
                fieldLabel: me.snippets.shortDescription,
                name: 'shortDescriptionField',
                inputValue: 1,
                uncheckedValue: 0
            }), Ext.create('Ext.form.field.Checkbox', {
                xtype: 'checkbox',
                labelWidth: 150,
                fieldLabel: me.snippets.additionalDescription,
                name: 'connectDescriptionField',
                inputValue: 1,
                uncheckedValue: 0
            })
        ];

        return Ext.create('Ext.form.FieldSet', {
            columnWidth: 1,
            title: me.snippets.productDescriptionLegend,
            layout: 'anchor',
            defaults: {
                anchor: '100%'
            },
            defaultType: 'textfield',
            items: me.articleDescriptions
        });

    },

    createLanguagesCombo: function() {
        var me = this;

        me.shopStore = Ext.create('Shopware.apps.Base.store.ShopLanguage').load({
            filters: [{
                property: 'default',
                value: false
            }]
        });

        return Ext.create('Ext.form.field.ComboBox', {
            multiSelect: true,
            displayField: 'name',
            valueField: 'id',
            name: 'exportLanguages',
            allowBlank: true,
            fieldLabel: me.snippets.exportLanguagesLabel,
            width: 435,
            store: me.shopStore,
            queryMode: 'local'
        });
    },


    /**
     * Returns a new progress bar for a detailed view of the exporting progress status
     *
     * @param name
     * @param text
     * @returns [object]
     */
    createProgressBar: function(name, text, value) {
        var me = this;

        me.progressBar = Ext.create('Ext.ProgressBar', {
            animate: true,
            name: 'progress-name',
            text: '{s name=config/message/done}Done{/s}',
            margin: '0 0 15',
            border: 1,
            style: 'border-width: 1px !important;',
            cls: 'left-align',
            value: 25
        });
        me.fireEvent('calculateFinishTime', me.progressBar);

        return me.progressBar;
    },

    /**
     * Populate export config form
     */
    populateForm: function () {
        var me = this,
            record = me.getRecord(),
            exportPriceMode = record.get('exportPriceMode');


        if (typeof(exportPriceMode) !== 'undefined' && exportPriceMode.length > 0){
            me.isExported = true;
        }

        if (purchasePriceInDetail == true && me.isExported) {
            me.purchasePriceTabPanel.setDisabled(true);
        }

        me.loadRecord(record);
    },

    getRecord: function () {
        var me = this,
            record = me.exportConfigStore.getAt(0);

        if (!record) {
            record = Ext.create('Shopware.apps.Connect.model.config.Export');
        }

        return record;
    },

    createProductContainer: function () {
        var me = this;

        return Ext.create('Ext.form.FieldSet', {
            columnWidth: 1,
            title: me.snippets.productSettingsLegend,
            defaultType: 'textfield',
            layout: 'anchor',
            items: [
                Ext.create('Ext.container.Container', {
                    columnWidth: 0.5,
                    layout: 'anchor',
                    border: false,
                    items: [
                        {
                            xtype: 'checkbox',
                            fieldLabel: me.snippets.excludeInactiveProducts,
                            name: 'excludeInactiveProducts',
                            inputValue: 1,
                            uncheckedValue: 0,
                            labelWidth: me.defaults.labelWidth
                        }
                    ]
                }),
                Ext.create('Ext.container.Container', {
                    columnWidth: 0.5,
                    padding: '0 20 0 0',
                    layout: 'anchor',
                    border: false,
                    items: [
                        {
                            xtype: 'fieldcontainer',
                            fieldLabel: me.snippets.autoProductSync,
                            defaultType: 'combo',
                            labelWidth: me.defaults.labelWidth,
                            items: [
                                {
                                    xtype:'combo',
                                    name:'autoUpdateProducts',
                                    queryMode:'local',
                                    store: Ext.create('Ext.data.Store', {
                                        fields: ['value', 'display'],
                                        data: [
                                            {
                                                "display": me.snippets.yes,
                                                "value": 1
                                            },
                                            {
                                                "display": "Cronjob",
                                                "value": 2
                                            },
                                            {
                                                "display": me.snippets.no,
                                                "value": 0
                                            }
                                        ]
                                    }),
                                    displayField:'display',
                                    valueField: 'value'
                                }
                            ]
                        }
                    ]
                })
            ]
        });
    }
});
//{/block}

