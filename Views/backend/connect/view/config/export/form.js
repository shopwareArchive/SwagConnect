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

    snippets: {
        save: '{s name=config/save}Save{/s}',
        cancel: '{s name=config/cancel}Cancel{/s}',
        productDescriptionFieldLabel: '{s name=config/export/product_description_field_label}Product description field{/s}',
        productDescriptionFieldHelp: Ext.String.format('{s name=config/export/product_description_field_help}Wählen Sie aus, welches Textfeld als Produkt-Beschreibung zu [0] exportiert werden soll und anderen Händlern zur Verfügung gestellt wird.{/s}',marketplaceName),
        autoProductSync: '{s name=config/export/auto_product_sync_label}Geänderte Produkte automatisch synchronisieren{/s}',
        autoPlayedChanges: Ext.String.format('{s name=config/export/changes_auto_played_label}Änderungen automatisch mit [0] synchronsieren{/s}', marketplaceName),
        emptyText: '{s name=config/export/empty_text_combo}Please choose{/s}',
        synchronization: '{s name=connect/synchronization}Synchronization{/s}',
        synchronizationBarDescription: Ext.String.format('{s name=config/synchronization_bar_description}Dieser Ladebalken zeigt die Dauer der Übertragung aller Bilder Ihres Shops zu [0] an. Es kann etwas länger dauern, bis Ihre Produkte auf [0] erscheinen. Das Einfügen / Updaten der Produkte ist jedoch abgeschlossen.{/s}', marketplaceName),
        priceConfiguration: '{s name=config/export/priceConfiguration}Preiskonfiguration{/s}',
        priceConfigurationDescription: Ext.String.format('{s name=config/export/label/export_price_description}Hier bestimmen Sie die Preise, die Sie zu [0] exportieren möchten. Alle Preise werden netto exportiert und können individuell mit Auf-und Abschlägen bearbeitet werden.<br><br>{/s}', marketplaceName),
        priceMode: '{s name=config/config/price/priceMode}Endkunden-VK{/s}',
        priceModeDescription: Ext.String.format('{s name=config/export/label/price_mode_description}Preiskalkulation auf [0]: <div class="ul-disc-type-holder"><ul><li>Exportieren Sie zum Beispiel nur einen Endkunden VK, können Sie über einen Abschlag einen Händlereinkaufspreis bestimmen.</li><li>Exportieren Sie einen Listenverkaufspreis, können Sie mit auf oder Abschlägen einen Händlereinkaufspreis definieren und optional eine unverbindliche Preisempfehlung für den Verkaufspreis definieren.</li><li>Exportieren Sie einen Endkunden Verkaufspreis und einen Listenverkaufspreis, können Sie optional Preise auf [0] bearbeiten.</li></ul></div>{/s}', marketplaceName),
        purchasePriceMode: '{s name=config/price/purchasePriceMode}Listenverkaufspreis-VK{/s}',
        exportLanguagesTitle: '{s name=config/export/exportLanguagesTitle}Sprachen{/s}',
        exportLanguagesLabel: '{s name=config/export/exportLanguagesLabel}Sprachauswahl{/s}',
        exportLanguagesHelpText: Ext.String.format('{s name=config/export/exportLanguagesHelpText}Hier legen Sie fest, welche Sprachen für Ihren Export zu [0] verwendet werden sollen. Wenn Sie die Produkte inkl. Übersetzung exportieren möchten, können Sie mehrere Sprachen auswählen. Wenn Sie dieses Feld leer lassen, wird automatisch die standard- Sprache Ihres Shops verwendet.{/s}', marketplaceName),
        yes: '{s name=connect/yes}Ja{/s}',
        no: '{s name=connect/no}Nein{/s}',
        edit: '{s name=connect/edit}Edit{/s}'
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
                                me.exportPurchasePriceCheckbox = me.createPurchasePriceCheckbox(),
                                me.exportPriceCheckbox = me.createPriceCheckbox()
                            ]
                        },
                        me.exportPurchasePriceMode = me.createPurchasePriceField(),
                        me.exportPriceMode = me.createPriceField()
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
        // pricing mapping fieldset should be not visible
        Ext.Ajax.request({
            scope: me,
            url: '{url controller=ConnectConfig action=isPricingMappingAllowed}',
            success: function(result, request) {
                var response = Ext.JSON.decode(result.responseText);
                if (response.success === false || response.isPricingMappingAllowed === false) {
                    me.priceMappingsFieldSet.setDisabled(true);
                }
                if (response.success === false || response.isPriceModeEnabled === false) {
                    me.exportPriceMode.setDisabled(true);
                    me.exportPriceCheckbox.setDisabled(true);
                }
                if (response.success === false || response.isPurchasePriceModeEnabled === false) {
                    me.exportPurchasePriceMode.setDisabled(true);
                    me.exportPurchasePriceCheckbox.setDisabled(true);
                }
            },
            failure: function() {
                me.priceMappingsFieldSet.setDisabled(true);
            }
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

    createPurchasePriceField: function() {
        var me = this;
        var dataIndexCustomerGroup = 'priceGroupForPurchasePriceExport';
        var dataIndexField = 'priceFieldForPurchasePriceExport';
        var helpText = '{s name=config/export/help/purchasePrice}Configure, which price field of which customer group should be exported as the product\'s merchant price{/s}';

        me.groupFieldForPurchasePrice = Ext.create('Ext.form.field.ComboBox', {
            queryMode: 'local',
            editable: false,
            name: dataIndexCustomerGroup,
            allowBlank: false,
            displayField: 'name',
            valueField: 'key',
            store: Ext.create('Shopware.apps.Connect.store.config.CustomerGroup'),
            supportText: '{s name=config/export/support/customer}customer group{/s}'
        });

        var comboTpl = new Ext.XTemplate(
            '<ul>',
            '<tpl for=".">',
            '<tpl if="available===true">',
            '{literal}<li role="option" class="x-boundlist-item">{name}</li>{/literal}',
            '<tpl else>',
            '{literal}<li role="option" class="x-boundlist-item" style="color: #e6e6e6;">{name}</li>{/literal}',
            '</tpl>',
            '</tpl>',
            '</ul>'
        );

        me.priceFieldForPurchasePrice = Ext.create('Ext.form.field.ComboBox', {
            name: dataIndexField,
            store: Ext.create('Shopware.apps.Connect.store.config.PriceGroup'),
            queryMode: 'local',
            editable: false,
            allowBlank: false,
            displayField: 'name',
            valueField: 'field',
            helpText: helpText,
            supportText: '{s name=config/export/support/price}price field{/s}',
            tpl: comboTpl
        });

        return Ext.create('Ext.form.FieldContainer', {
            layout: 'hbox',
            columnWidth: .75,
            items: [
                me.groupFieldForPurchasePrice,
                me.priceFieldForPurchasePrice
            ]
        });
    },

    /**
     * Creates a price config fieldcontainer for price or purchasePrice
     *
     * @return Object
     */
    createPriceField: function () {
        var me = this,
            dataIndexCustomerGroup,
            dataIndexField,
            helpText;

            dataIndexCustomerGroup = 'priceGroupForPriceExport';
            dataIndexField = 'priceFieldForPriceExport';
            helpText = '{s name=config/export/help/price}Configure, which price field of which customer group should be exported as the product\'s end user price{/s}';


        me.groupFieldForPrice = Ext.create('Ext.form.field.ComboBox', {
            queryMode: 'local',
            editable: false,
            name: dataIndexCustomerGroup,
            allowBlank: false,
            displayField: 'name',
            valueField: 'key',
            store: Ext.create('Shopware.apps.Connect.store.config.CustomerGroup'),
            supportText: '{s name=config/export/support/customer}customer group{/s}'
        });

        var comboTpl = new Ext.XTemplate(
            '<ul>',
            '<tpl for=".">',
            '<tpl if="available===true">',
            '{literal}<li role="option" class="x-boundlist-item">{name}</li>{/literal}',
            '<tpl else>',
            '{literal}<li role="option" class="x-boundlist-item" style="color: #e6e6e6;">{name}</li>{/literal}',
            '</tpl>',
            '</tpl>',
            '</ul>'
        );
        me.priceFieldForPrice = Ext.create('Ext.form.field.ComboBox', {
            name: dataIndexField,
            store: Ext.create('Shopware.apps.Connect.store.config.PriceGroup'),
            queryMode: 'local',
            editable: false,
            allowBlank: false,
            displayField: 'name',
            valueField: 'field',
            helpText: helpText,
            supportText: '{s name=config/export/support/price}price field{/s}',
            tpl: comboTpl
        });

        return Ext.create('Ext.form.FieldContainer', {
            layout: 'hbox',
            columnWidth: .75,
            items: [
                me.groupFieldForPrice,
                me.priceFieldForPrice
            ]
        });
    },

    /**
     * Creates price checkbox
     * it's used to show only which price type is selected
     *
     * @returns Ext.form.field.Checkbox
     */
    createPriceCheckbox: function() {
        var me = this;

        return Ext.create('Ext.form.field.Checkbox', {
            boxLabel: me.snippets.priceMode,
            name: 'exportPriceMode',
            inputValue: 'price'
        });
    },

    /**
     * Creates purchase price checkbox
     * it's used to show only which price type is selected
     *
     * @returns Ext.form.field.Checkbox
     */
    createPurchasePriceCheckbox: function() {
        var me = this;

        return Ext.create('Ext.form.field.Checkbox', {
            boxLabel: me.snippets.purchasePriceMode,
            name: 'exportPriceMode',
            inputValue: 'purchasePrice',
            margin: '15 0 0 0'
        });
    },

    loadPriceAndGroupStores: function(record) {
        var me = this;

        me.groupFieldForPrice.store.load();
        me.groupFieldForPurchasePrice.store.load();

        me.priceFieldForPrice.store.load({
            params: {
                'customerGroup': record.get('priceGroupForPriceExport')
            }
        });

        me.priceFieldForPurchasePrice.store.load({
                params: {
                    'customerGroup': record.get('priceGroupForPurchasePriceExport')
                }
        });
    },

    /**
     * Populate export config form
     */
    populateForm: function () {
        var me = this;
        var record = me.getRecord();

        me.loadPriceAndGroupStores(record);
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
                            ['attribute.connectProductDescription', 'attribute.connectProductDescription'],
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
        });
    }
});
//{/block}

