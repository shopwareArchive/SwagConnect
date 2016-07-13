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
        productDescriptionLegend: '{s name=config/export/product_description_legend}Product description{/s}',
        productDescriptionFieldLabel: '{s name=config/export/product_description_field_label}Product description field{/s}',
        productDescriptionNotSelected: '{s name=config/export/product_description_not_selected}Please select product description{/s}',
        purchasePriceMode: '{s name=config/price/purchasePriceMode}Purchase price{/s}',
        priceMode: '{s name=config/config/price/priceMode}End customer price{/s}',
        priceModeNotSelected: '{s name=config/config/price/price_mode_not_selected}Please select price mode{/s}',
        emptyText: '{s name=config/export/empty_text_combo}Please choose{/s}',
        price: '{s name=detail/price/price}Price{/s}',
        pseudoPrice: '{s name=detail/price/pseudo_price}Pseudo price{/s}',
        basePrice: '{s name=detail/price/base_price}Purchase price{/s}'
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
        this.addEvents('saveExportSettings', 'rejectPriceConfigChanges');
    },

    loadPriceStores: function() {
        var me = this;

        me.purchasePriceTabPanel.items.each(function(tab){
            tab.getStore().load({
                params: {
                    'customerGroup': tab.customerGroup.get('key'),
                    'customerExportMode': true
                }
            });
        });

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

        me.purchasePriceTabPanel = me.createPriceTab();

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
            columns: [
                {
                    header: '',
                    flex: 1
                }, {
                    header: me.snippets.price,
                    dataIndex: 'price',
                    columnType: 'price',
                    xtype: 'checkboxcolumn',
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
                    listeners: {
                        beforecheckchange: function(column, view, cell, recordIndex, cellIndex){
                            me.fireEvent('rejectPriceConfigChanges', column, view, cell, recordIndex, cellIndex);
                        }
                    }
                }, {

                    header: me.snippets.basePrice,
                    dataIndex: 'basePrice',
                    columnType: 'basePrice',
                    xtype: 'checkboxcolumn',
                    listeners: {
                        beforecheckchange: function(column, view, cell, recordIndex, cellIndex){
                            me.fireEvent('rejectPriceConfigChanges', column, view, cell, recordIndex, cellIndex);
                        }
                    }
                }
            ]
        });
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
            title: me.snippets.productDescriptionLegend,
            layout: 'anchor',
            defaults: {
                anchor: '100%'
            },
            defaultType: 'textfield',
            items: [me.productDescriptionCombo]
        });
    },

    //todo: move this into main controller
    rejectChanges: function (type) {
        var tabs = type.up('panel').up('panel').items;

        tabs.each(function(tab){
            tab.getStore().rejectChanges();
        });
    },

    //todo: move this into main controller
    processPricePanel: function(tabPanel, exportMode) {
        var me = this,
            priceTypes = ['price', 'pseudoPrice', 'basePrice'],
            exportPriceType;

        switch (exportMode) {
            case 'purchasePrice':
                exportPriceType = 'ForPurchasePriceExport';
                break;
            case 'price':
                exportPriceType = 'ForPriceExport';
                break;
        }

        tabPanel.items.each(function(tab) {
            if (tab.getStore().getUpdatedRecords().length > 0) {
                me.priceParams['priceGroup' + exportPriceType] = tab.customerGroup.get('key');
                me.priceParams.exportPriceMode.push(exportMode);

                for (var i = 0; i < priceTypes.length; i++){
                    if (tab.getStore().getAt(0).get(priceTypes[i]) == true) {
                        me.priceParams['priceField' + exportPriceType] = priceTypes[i];
                    }
                }
            }
        });
    },

    resetDefaultPriceParams: function () {
        var me = this;

        me.priceParams = {
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
                me.resetDefaultPriceParams();
                me.processPricePanel(me.purchasePriceTabPanel, 'purchasePrice');
                me.processPricePanel(me.priceTabPanel, 'price');

                if (me.priceParams.exportPriceMode.length == 0) {
                    return Shopware.Notification.createGrowlMessage(me.snippets.exportTitle, me.snippets.priceModeNotSelected);
                }

                if (me.productDescriptionCombo.getValue()) {
                    me.priceParams.alternateDescriptionField = me.productDescriptionCombo.getValue();
                } else {
                    return Shopware.Notification.createGrowlMessage(me.snippets.exportTitle, me.snippets.productDescriptionNotSelected);
                }

                me.fireEvent('saveExportSettings', me.priceParams, btn);
            },
            cls: 'primary'
        });

        buttons.push(cancelButton);
        buttons.push(saveButton);

        return buttons;
    }
});
//{/block}

