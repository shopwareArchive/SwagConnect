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
        productDescriptionFieldHelp: Ext.String.format('{s name=config/export/product_description_field_help}Wählen Sie aus, welches Textfeld als Produkt-Beschreibung zu [0] exportiert werden soll und anderen Händlern zur Verfügung gestellt wird.{/s}',marketplaceName),
        autoProductSync: '{s name=config/export/auto_product_sync_label}Geänderte Produkte automatisch synchronisieren{/s}',
        autoPlayedChanges: Ext.String.format('{s name=config/export/changes_auto_played_label}Änderungen automatisch mit [0] synchronsieren{/s}', marketplaceName),
        emptyText: '{s name=config/export/empty_text_combo}Please choose{/s}',
        synchronization: '{s name=synchronization}Synchronization{/s}',
        synchronizationBarDescription: Ext.String.format('{s name=config/synchronization_bar_description}Dieser Ladebalken zeigt die Dauer der Übertragung aller Bilder Ihres Shops zu [0] an. Es kann etwas länger dauern, bis Ihre Produkte auf [0] erscheinen. Das Einfügen / Updaten der Produkte ist jedoch abgeschlossen.{/s}', marketplaceName),
        priceConfiguration: '{s name=config/export/priceConfiguration}Preiskonfiguration{/s}',
        priceConfigurationDescription: Ext.String.format('{s name=config/export/label/export_price_description}Hier bestimmen Sie die Preise, die Sie zu [0] exportieren möchten. Alle Preise werden netto exportiert und können individuell mit Auf-und Abschlägen bearbeitet werden.<br><br>{/s}', marketplaceName),
        priceMode: '{s name=config/config/price/priceMode}Endkunden-VK{/s}',
        priceModeDescription: Ext.String.format('{s name=config/export/label/price_mode_description}Preiskalkulation auf [0]: <div class="ul-disc-type-holder"><ul><li>Exportieren Sie zum Beispiel nur einen Endkunden VK, können Sie über einen Abschlag einen Händlereinkaufspreis bestimmen.</li><li>Exportieren Sie einen Listenverkaufspreis, können Sie mit auf oder Abschlägen einen Händlereinkaufspreis definieren und optional eine unverbindliche Preisempfehlung für den Verkaufspreis definieren.</li><li>Exportieren Sie einen Endkunden Verkaufspreis und einen Listenverkaufspreis, können Sie optional Preise auf [0] bearbeiten.</li></ul></div>{/s}', marketplaceName),
        purchasePriceMode: '{s name=config/price/purchasePriceMode}Listenverkaufspreis-VK{/s}',
        exportLanguagesTitle: '{s name=config/export/exportLanguagesTitle}Sprachen{/s}',
        exportLanguagesLabel: '{s name=config/export/exportLanguagesLabel}Sprachauswahl{/s}',
        exportLanguagesHelpText: Ext.String.format('{s name=config/export/exportLanguagesHelpText}Hier legen Sie fest, welche Sprachen für Ihren Export zu [0] verwendet werden sollen. Wenn Sie die Produkte inkl. Übersetzung exportieren möchten, können Sie mehrere Sprachen auswählen. Wenn Sie dieses Feld leer lassen, wird automatisch die standard- Sprache Ihres Shops verwendet.{/s}', marketplaceName),
        edit: '{s name=edit}Edit{/s}'
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
        var syncFieldset = me.getSyncFieldset();
        var container = me.createProductContainer();

        me.priceMappingsFieldSet = Ext.create('Ext.form.FieldSet', {
            title: me.snippets.priceConfiguration ,
            disabled: false,
            items: [
                {
                    xtype: 'label',
                    html: me.snippets.priceConfigurationDescription
                },
                {
                    xtype: 'container',
                    layout: 'column',
                    margin: '0 0 30 0',
                    items: [
                        {
                            xtype: 'checkboxgroup',
                            columns: 1,
                            vertical: true,
                            columnWidth: .25,
                            items: [
                                {
                                    boxLabel: me.snippets.priceMode,
                                    name: 'exportPriceMode',
                                    readOnly: true,
                                    inputValue: 'price'
                                },
                                {
                                    boxLabel: me.snippets.purchasePriceMode,
                                    name: 'exportPriceMode',
                                    inputValue: 'purchasePrice',
                                    readOnly: true,
                                    margin: '15 0 0 0'
                                }
                            ]
                        },
                        me.exportPriceMode = me.createPriceField('price'),
                        me.exportPurchasePriceMode = me.createPriceField('purchasePrice')
                    ]
                },
                {
                    xtype: 'label',
                    html: me.snippets.priceModeDescription
                }
            ]
        });

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

        // if there is exported product
        // pricing mapping should be disabled
        Ext.Ajax.request({
            scope: me,
            url: '{url controller=BepadoConfig action=isPricingMappingAllowed}',
            success: function(result, request) {
                var response = Ext.JSON.decode(result.responseText);
                if (response.success === false || response.isPricingMappingAllowed === false) {
                    me.priceMappingsFieldSet.setDisabled(true);
                }
                if (response.success === false || response.isPriceModeEnabled === false) {
                    me.exportPriceMode.setDisabled(true);
                }
                if (response.success === false || response.isPurchasePriceModeEnabled === false) {
                    me.exportPurchasePriceMode.setDisabled(true);
                }
            },
            failure: function() { }
        });

        Ext.getStore('export.List').load();

        return [
            me.priceMappingsFieldSet,
            syncFieldset,
            container,
            me.languagesExportFieldset
        ];
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

    getSyncFieldset: function() {
        var me = this;

        return Ext.create('Ext.form.FieldSet', {
            columnWidth: 1,
            title: me.snippets.synchronization,
            defaultType: 'textfield',
            layout: 'anchor',
            html: me.snippets.synchronizationBarDescription,
            items: [ me.createProgressBar() ]
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
     * Creates a price config fieldcontainer for price or purchasePrice
     *
     * @return Object
     */
    createPriceField: function (type) {
        var me = this,
            dataIndexCustomerGroup,
            dataIndexField,
            helpText;

        if (type == 'price') {
            dataIndexCustomerGroup = 'priceGroupForPriceExport';
            dataIndexField = 'priceFieldForPriceExport';
            helpText = '{s name=config/export/help/price}Configure, which price field of which customer group should be exported as the product\'s end user price{/s}';
        } else if (type == 'purchasePrice') {
            dataIndexCustomerGroup = 'priceGroupForPurchasePriceExport';
            dataIndexField = 'priceFieldForPurchasePriceExport';
            helpText = '{s name=config/export/help/purchasePrice}Configure, which price field of which customer group should be exported as the product\'s merchant price{/s}';
        } else {
            return { };
        }

        return Ext.create('Ext.form.FieldContainer', {
            layout: 'hbox',
            columnWidth: .75,
            items: [
                {
                    xtype: 'combobox',
                    queryMode: 'local',
                    editable: false,
                    name: dataIndexCustomerGroup,
                    allowBlank: false,
                    displayField: 'name',
                    valueField: 'key',
                    store: Ext.create('Shopware.apps.Bepado.store.config.CustomerGroup', { }).load({
                        params:{
                            priceField: type
                        }
                    }),
                    supportText: '{s name=config/export/support/customer}customer group{/s}'
                },
                {
                    xtype: 'combobox',
                    name: dataIndexField,
                    store: Ext.create('Shopware.apps.Bepado.store.config.PriceGroup', { }).load({}),
                    queryMode: 'local',
                    editable: false,
                    allowBlank: false,
                    displayField: 'name',
                    valueField: 'field',
                    helpText: helpText,
                    supportText: '{s name=config/export/support/price}price field{/s}'

                }
            ]
        });

    },

    /**
     * Populate export config form
     */
    populateForm: function () {
        var me = this;

        me.loadRecord(me.getRecord());
    },

    getRecord: function () {
        var me = this,
            record = me.exportConfigStore.getAt(0);

        if (!record) {
            record = Ext.create('Shopware.apps.Bepado.model.config.Export');
        }

        return record;
    },

    createProductContainer: function () {
        var me = this;

        return Ext.create('Ext.container.Container', {
            columnWidth: 1,
            padding: '0 0 20 0',
            layout: 'fit',
            border: false,
            items: [
                {
                    xtype: 'combobox',
                    fieldLabel: me.snippets.productDescriptionFieldLabel,
                    emptyText: me.snippets.emptyText,
                    helpText: me.snippets.productDescriptionFieldHelp,
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
    }
});
//{/block}

