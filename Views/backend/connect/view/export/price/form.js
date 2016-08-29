//{namespace name=backend/connect/view/main}

//{block name="backend/connect/view/export/price/form"}
Ext.define('Shopware.apps.Connect.view.export.price.Form', {
    extend: 'Ext.form.Panel',
    alias: 'widget.connect-export-price-form',

    border: false,
    layout: 'anchor',
    autoScroll: true,
    region: 'center',
    bodyPadding: 10,
    height: '100%',

    snippets: {
        exportTitle: '{s name=connect/tab_panel/export}Export{/s}',
        save: '{s name=config/save}Save{/s}',
        cancel: '{s name=config/cancel}Cancel{/s}',
        productSettingsLegend: '{s name=config/export/product_settings_legend}Product settings{/s}',
        productDescriptionFieldLabel: '{s name=config/export/product_description_field_label}Product description field{/s}',
        productDescriptionNotSelected: '{s name=config/export/product_description_not_selected}Please select product description{/s}',
        purchasePriceMode: '{s name=config/price/purchasePriceMode}Purchase price{/s}',
        priceMode: '{s name=config/config/price/priceMode}End customer price{/s}',
        priceModeNotSelected: '{s name=config/config/price/price_mode_not_selected}Please select price mode{/s}',
        emptyText: '{s name=config/export/empty_text_combo}Please choose{/s}',
        price: '{s name=export/price/price}Price{/s}',
        pseudoPrice: '{s name=export/price/pseudo_price}Pseudo price{/s}',
        purchasePrice: '{s name=export/price/purchase_price}Purchase price{/s}',
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

        me.loadPriceStores();

        me.callParent(arguments);
    },

    registerEvents: function() {
        this.addEvents('saveExportSettings', 'rejectPriceConfigChanges', 'collectPriceParams');
    },

    loadPriceStores: function() {
        var me = this;

        if (purchasePriceInDetail == false) {
            me.purchasePriceTabPanel.items.each(function(tab){
                tab.getStore().load({
                    params: {
                        'customerGroup': tab.customerGroup.get('key'),
                        'customerExportMode': true
                    }
                });
            });
        }

        me.priceTabPanel.items.each(function(tab){
            tab.getStore().load({
                params: {
                    'customerGroup': tab.customerGroup.get('key'),
                    'customerExportMode': true
                }
            });
        });
    },

    createElements: function () {
        var me = this;

        return [
            me.createPurchasePriceContainer(),
            me.createPriceContainer(),
            me.createProductContainer()
        ];
    },

    createPurchasePriceContainer: function () {
        var me = this;

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

        return Ext.create('Ext.form.FieldSet', {
            columnWidth: 1,
            title: me.snippets.purchasePriceMode,
            layout: 'anchor',
            width: '90%',

            items: [
                me.purchasePriceTabPanel
            ]
        });
    },

    createPriceContainer: function () {
        var me = this;

        me.priceTabPanel = me.createPriceTab();

        return Ext.create('Ext.form.FieldSet', {
            columnWidth: 1,
            title: me.snippets.priceMode,
            layout: 'anchor',
            width: '90%',

            items: [
                me.priceTabPanel
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


    createProductContainer: function () {
        var me = this;

        me.productDescriptionCombo = Ext.create('Ext.form.field.ComboBox', {
            fieldLabel: me.snippets.productDescriptionFieldLabel,
            emptyText: me.snippets.emptyText,
            helpText: me.snippets.productDescriptionFieldHelp,
            name: 'alternateDescriptionField',
            labelWidth: 200,
            store: new Ext.data.SimpleStore({
                fields: ['value', 'text'],
                data: [
                    ['attribute.connectProductDescription', 'attribute.connectProductDescription'],
                    ['a.description', 'Artikel-Kurzbeschreibung'],
                    ['a.descriptionLong', 'Artikel-Langbeschreibung']
                ]
            }),
            queryMode: 'local',
            displayField: 'text',
            valueField: 'value',
            editable: false
        });

        return Ext.create('Ext.form.FieldSet', {
            columnWidth: 1,
            title: me.snippets.productSettingsLegend,
            layout: 'anchor',
            defaults: {
                anchor: '100%'
            },
            defaultType: 'textfield',
            items: [me.productDescriptionCombo]
        });
    },

    createPriceParams: function () {
        return {
            'autoUpdateProducts' : 1,
            'exportLanguages': [],
            'exportPriceMode': []
        };
    },

    /**
     * Returns form buttons, save and cancel
     * @returns Array
     */
    getFormButtons: function () {
        var me = this,
            buttons = ['->'];

        var cancelButton = Ext.create('Ext.button.Button', {
            text: me.snippets.cancel,
            handler: function (btn) {
                btn.up('window').close();
            }
        });

        var saveButton = Ext.create('Ext.button.Button', {
            text: me.snippets.save,
            action: 'save-export-settings',
            handler: function (btn) {
                var priceParams = me.createPriceParams();
                if (purchasePriceInDetail == false) {
                    me.fireEvent('collectPriceParams', me.purchasePriceTabPanel, 'purchasePrice', priceParams);
                } else if (purchasePriceInDetail == true && me.purchasePriceTabPanel.getValue()) {
                    priceParams.exportPriceMode.push('purchasePrice');
                }

                me.fireEvent('collectPriceParams', me.priceTabPanel, 'price', priceParams);

                if (me.productDescriptionCombo.getValue()) {
                    priceParams.alternateDescriptionField = me.productDescriptionCombo.getValue();
                }

                me.fireEvent('saveExportSettings', priceParams, btn);
            },
            cls: 'primary'
        });

        buttons.push(cancelButton);
        buttons.push(saveButton);

        return buttons;
    }
});
//{/block}

