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
//{block name="backend/connect/view/config/general/form"}
Ext.define('Shopware.apps.Connect.view.config.general.Form', {
    extend: 'Ext.form.Panel',
    alias: 'widget.connect-config-form',

    border: false,
    layout: 'anchor',
    autoScroll: true,
    region: 'center',
    bodyPadding: 10,

    /**
     * Contains the field set defaults.
     */
    defaults: {
        labelWidth: 170,
        importSettingsLabelWidth: 190,
        anchor: '100%'
    },


    snippets: {
        apiKeyHeader: '{s name=config/main/api_key}API-Key{/s}',
        apiKeyDescription: Ext.String.format('{s name=config/api_key_description}Bitte hinterlegen Sie an dieser Stelle Ihren API-Key, um eine Verbindung zu [0] aufzubauen. Unter dem folgenden Link können Sie Ihren API-Key einsehen: <a href=[1]/settings/exchange target=_blank>[1]/settings/exchange</a><br>Sie können viele verschiedene ERP- und Shopsysteme an [0] andocken, wozu Ihnen eine Vielzahl von Schnittstellen zur Verfügung steht - <a href=http://info.bepado.de/schnittstellen target=_blank>Mehr Info</a><br><br>{/s}', marketplaceName, marketplaceNetworkUrl),
        apiKeyCheck: '{s name=config/api_key_check}Validate{/s}',
        save: '{s name=config/save}Save{/s}',
        cancel: '{s name=config/cancel}Cancel{/s}',
        detailPageHintLabel: '{s name=config/detail_page_hint}Show marketplace hint on article detail page{/s}',
        noIndexLabel: Ext.String.format('{s name=config/noindex_label}Setze »noindex« meta-tag für [0]-Produkte{/s}', marketplaceName),
        basketHintLabel: '{s name=config/basket_hint_label}Show marketplace hint in basket{/s}',
        connectAttributeLabel: Ext.String.format('{s name=config/connect_attribute_label}[0]-Attribut{/s}', marketplaceName),
        alternativeHostLabel: Ext.String.format('{s name=config/connect_alternative_host}Alternativer [0]-Host (nur für Testzwecke){/s}', marketplaceName),
        logLabel: '{s name=config/log_label}Logging aktivieren{/s}',
        logDescription: Ext.String.format('{s name=config/log_description}[0]-Anfragen mitschreiben{/s}', marketplaceName),
        shippingCostsLabel: '{s name=config/plus_shipping_costs}Shipping costs page{/s}',
        exportDomainLabel: '{s name=config/alternative_export_url}Alternative export URL{/s}',
        hasSslLabel: '{s name=config/has_ssl_label}My shop has SSL{/s}',
        basicHeader: '{s name=config/basic}Basic{/s}',
        unitsHeader: '{s name=navigation/units}Einheiten{/s}',
        unitsFieldsetDescription: Ext.String.format('{s name=config/units/description}Hier ordnen Sie die Einheiten aus Ihrem Shop den Standard-Einheiten in [0] zu.{/s}',marketplaceName),
        importSettingsHeader: '{s name=config/import_settings_header}Import Einstellungen{/s}',
        createCategoriesAutomatically: '{s name=config/import/categories/create_automatically}Kategorien automatisch anlegen{/s}',
        activateProductsAutomatically: '{s name=config/import/products/activate_automatically}Produkte automatisch aktivieren{/s}',
        createUnitsAutomatically: '{s name=config/import/units/create_automatically}Einheiten automatisch anlegen{/s}',
        separateShippingLabel: '{s name=config/separate_shipping_label}Versandkosten als separate Position im Warenkorb ausgeben{/s}',
        advancedHeader: '{s name=config/advanced}Advanced{/s}'
    },

    initComponent: function() {
        var me = this;

        me.items = me.createElements();
        me.dockedItems = [{
                xtype: 'toolbar',
                dock: 'bottom',
                ui: 'shopware-ui',
                cls: 'shopware-toolbar',
                items: me.getFormButtons()
            }];

        me.generalConfigStore = Ext.create('Shopware.apps.Connect.store.config.General').load({
            callback:function() {
                me.populateForm();
            }
        });

        me.callParent(arguments);
    },

    /**
     * Creates form elements
     * @return Array
     */
    createElements: function() {
        var me = this;
            basicConfigFieldset = me.getBasicConfigFieldset(),
            unitsFieldset = me.getUnitsFieldset(),
            advancedConfigFieldset = me.getAdvancedConfigFieldset(),
            elements = [];

        if (me.isDefaultShop()) {
            elements.push(me.getApiKeyFieldset());
            if (defaultMarketplace == false) {
                // extended import settings are available
                // only for SEM shops
                elements.push(me.getImportSettingsFieldset());
            }
        }
        elements.push(basicConfigFieldset);
        elements.push(unitsFieldset);
        elements.push(advancedConfigFieldset);

        return elements;
    },

    /**
     * Returns form buttons, save and cancel
     * @returns Array
     */
    getFormButtons: function() {
        var me = this,
            buttons = ['->'];

        var saveButton = Ext.create('Ext.button.Button', {
            text: me.snippets.save,
            action: 'save-general-config',
            cls: 'primary'
        });

        var cancelButton = Ext.create('Ext.button.Button', {
            text: me.snippets.cancel,
            handler: function(btn) {
                btn.up('window').close();
            }
        });

        buttons.push(cancelButton);
        buttons.push(saveButton);

        return buttons;
    },

    /**
     * Returns API key field set
     *
     * @return Ext.form.FieldSet
     */
    getApiKeyFieldset: function() {
        var me = this;

        var apiFieldset = Ext.create('Ext.form.FieldSet', {
            columnWidth: 1,
            title: me.snippets.apiKeyHeader,
            defaultType: 'textfield',
            layout: 'anchor',
            items: [
                {
                    xtype: 'container',
                    html: me.snippets.apiKeyDescription
                }, {
                    xtype: 'container',
                    layout: 'hbox',
                    items: [
                        {
                            xtype: 'textfield',
                            fieldLabel: me.snippets.apiKeyHeader,
                            labelWidth: 100,
                            name: 'apiKey',
                            flex: 5,
                            padding: '0 20 10 0'
                        }, {
                            xtype: 'button',
                            flex: 1,
                            height: 27,
                            text: me.snippets.apiKeyCheck,
                            handler: function(btn) {
                                var apiField = btn.up('form').down('textfield[name=apiKey]'),
                                    apiKey = apiField.getValue();
                                Ext.Ajax.request({
                                    scope: this,
                                    url: window.location.pathname + 'connect/verifyApiKey',
                                    success: function(result, request) {
                                        var response = Ext.JSON.decode(result.responseText);
                                        Ext.get(apiField.inputEl).setStyle('background-color', response.success ? '#C7F5AA' : '#FFB0AD');
                                        if(response.message) {
                                            Shopware.Notification.createGrowlMessage(
                                                btn.title,
                                                response.message
                                            );
                                        }
                                    },
                                    failure: function() { },
                                    params: { apiKey: apiKey }
                                });
                            }
                        }
                    ]
                }, {
                    xtype: 'checkbox',
                    name: 'hasSsl',
                    fieldLabel: me.snippets.hasSslLabel,
                    inputValue: 1,
                    uncheckedValue: 0,
                    labelWidth: me.defaults.labelWidth,
                    helpText: '{s name=config/help/has_ssl_help_text}If your store has installed SSL certificate please select the checkbox and save your changes. Then verify the API key.{/s}'
                }
            ]
        });

        return apiFieldset;
    },

    /**
     * Creates basic configuration field set
     * @return Ext.form.FieldSet
     */
    getBasicConfigFieldset: function() {
        var me = this,
            items = [],
            leftElements = me.createLeftElements(),
            rightElements = me.createRightElements(),
            bottomElements = me.createBottomElements();

        items.push(leftElements);
        items.push(rightElements);
        items.push(bottomElements);

        if (me.defaultShop) {
            var defaultElements = me.createDefaultElements();
            items.push(defaultElements);
        }

        var fieldset = Ext.create('Ext.form.FieldSet', {
            layout: 'column',
            title: me.snippets.basicHeader,
            defaults: {
                labelWidth: 170,
                anchor: '100%'
            },
            items: items
        });

        return fieldset;
    },

    /**
     * Returns Import settings field set
     *
     * @return Ext.form.FieldSet
     */
    getImportSettingsFieldset: function() {
        var me = this;

        var importSettingsFieldset = Ext.create('Ext.form.FieldSet', {
            columnWidth: 1,
            title: me.snippets.importSettingsHeader,
            defaultType: 'checkbox',
            layout: 'anchor',
            items: [
                {
                    xtype: 'checkbox',
                    name: 'createCategoriesAutomatically',
                    fieldLabel: me.snippets.createCategoriesAutomatically,
                    inputValue: 1,
                    uncheckedValue: 0,
                    labelWidth: me.defaults.importSettingsLabelWidth
                }, {
                    xtype: 'checkbox',
                    name: 'activateProductsAutomatically',
                    fieldLabel: me.snippets.activateProductsAutomatically,
                    inputValue: 1,
                    uncheckedValue: 0,
                    labelWidth: me.defaults.importSettingsLabelWidth
                }, {
                    xtype: 'checkbox',
                    name: 'createUnitsAutomatically',
                    fieldLabel: me.snippets.createUnitsAutomatically,
                    inputValue: 1,
                    uncheckedValue: 0,
                    labelWidth: me.defaults.importSettingsLabelWidth
                }
            ]
        });

        return importSettingsFieldset;
    },

    getUnitsFieldset: function() {
        var me = this,
            items = [];

        items.push({
            xtype: 'container',
            html: me.snippets.unitsFieldsetDescription
        });
        items.push({
            xtype: 'connect-units-mapping',
            width: '100%',
            padding: '10 0 0 0'
        });

        var fieldset = Ext.create('Ext.form.FieldSet', {
            layout: 'vbox',
            title: me.snippets.unitsHeader,
            collapsible: true,
            collapsed: false,
            defaults: {
                anchor: '100%'
            },
            items: items
        });

        return fieldset;
    },

    /**
     * Creates advanced configuration field set
     * @return Ext.form.FieldSet
     */
    getAdvancedConfigFieldset: function() {
        var me = this,
            items = [];

        var leftContainer = Ext.create('Ext.container.Container', {
            columnWidth: 0.5,
            layout: 'anchor',
            border: false,
            items: [
                {
                    xtype: 'checkbox',
                    name: 'detailProductNoIndex',
                    fieldLabel: me.snippets.noIndexLabel,
                    inputValue: 1,
                    uncheckedValue: 0,
                    labelWidth: me.defaults.labelWidth
                }
            ]
        });
        items.push(leftContainer);

        if (me.isDefaultShop()) {
            var bottomContainer = Ext.create('Ext.container.Container', {
                columnWidth: 1,
                layout: 'anchor',
                border: false,
                items: [
                    {
                        xtype: 'textfield',
                        name: 'connectDebugHost',
                        anchor: '100%',
                        fieldLabel: me.snippets.alternativeHostLabel,
                        labelWidth: me.defaults.labelWidth,
                        helpText: Ext.String.format('{s name=config/help/debug_host}Nutze den angegebenen Host statt des [0]-Hosts. Nur für Testzweckecke{/s}', marketplaceName)
                    }, {
                        xtype: 'textfield',
                        name: 'exportDomain',
                        anchor: '100%',
                        fieldLabel: me.snippets.exportDomainLabel,
                        labelWidth: me.defaults.labelWidth,
                        helpText: '{s name=config/help/alternative_export_url}Use the given URL instead of default product export URL, e.g. http://shop.de/marketplace_product_gateway/product/id/{/s}'
                    }, {
                        xtype: 'fieldcontainer',
                        fieldLabel: me.snippets.logLabel,
                        defaultType: 'checkboxfield',
                        labelWidth: me.defaults.labelWidth,
                        items: [
                            {
                                boxLabel: me.snippets.logDescription,
                                name: 'logRequest',
                                inputValue: 1,
                                uncheckedValue: 0
                            }
                        ]
                    }, {
                        xtype: 'fieldcontainer',
                        fieldLabel: me.snippets.separateShippingLabel,
                        defaultType: 'checkboxfield',
                        labelWidth: me.defaults.labelWidth,
                        items: [
                            {
                                name: 'showShippingCostsSeparately',
                                inputValue: 1,
                                uncheckedValue: 0
                            }
                        ]
                    }
                ]
            });
            items.push(bottomContainer);
        }

        var fieldset = Ext.create('Ext.form.FieldSet', {
            layout: 'column',
            title: me.snippets.advancedHeader,
            collapsible: true,
            collapsed: true,
            defaults: {
                labelWidth: 170,
                anchor: '100%'
            },
            items: items
        });

        return fieldset;
    },

    /**
     * Creates the field set items which are displayed in the left column
     * @return Ext.container.Container
     */
    createLeftElements: function () {
        var me = this;

        var leftContainer = Ext.create('Ext.container.Container', {
            columnWidth: 0.5,
            padding: '0 20 0 0',
            layout: 'anchor',
            border: false,
            items: [
                {
                    xtype: 'checkbox',
                    name: 'detailShopInfo',
                    fieldLabel: me.snippets.detailPageHintLabel,
                    inputValue: 1,
                    uncheckedValue: 0,
                    labelWidth: me.defaults.labelWidth
                }
            ]
        });

        return leftContainer;
    },

    /**
     * Creates the field set items which are displayed in the right column
     * @return Ext.container.Container
     */
    createRightElements: function () {
        var me = this;

        var rightContainer = Ext.create('Ext.container.Container', {
            columnWidth: 0.5,
            layout: 'anchor',
            border: false,
            items: [
                {
                    xtype: 'checkbox',
                    name: 'checkoutShopInfo',
                    fieldLabel: me.snippets.basketHintLabel,
                    inputValue: 1,
                    uncheckedValue: 0,
                    labelWidth: me.defaults.labelWidth
                }
            ]
        });

        return rightContainer;
    },

    /**
     * Creates the field set items which are displayed only for default shop
     * @return Ext.container.Container
     */
    createDefaultElements: function() {
        var me = this;

        var attributeStore = new Ext.data.ArrayStore({
            fields: ['id', 'name'],
            data: [
                [1, 'attr1'],
                [2, 'attr2'],
                [3, 'attr3'],
                [4, 'attr4'],
                [5, 'attr5'],
                [6, 'attr6'],
                [7, 'attr7'],
                [8, 'attr8'],
                [9, 'attr9'],
                [10, 'attr10'],
                [11, 'attr11'],
                [12, 'attr12'],
                [13, 'attr13'],
                [14, 'attr14'],
                [15, 'attr15'],
                [16, 'attr16'],
                [17, 'attr17'],
                [18, 'attr18'],
                [19, 'attr19'],
                [20, 'attr20']
            ]
        });

        var defaultContainer = Ext.create('Ext.container.Container', {
            columnWidth: 1,
            layout: 'anchor',
            border: false,
            items: [
                {
                    xtype: 'combo',
                    name: 'connectAttribute',
                    anchor: '100%',
                    required: true,
                    editable: false,
                    valueField: 'id',
                    displayField: 'name',
                    fieldLabel: me.snippets.connectAttributeLabel,
                    store: attributeStore,
                    labelWidth: me.defaults.labelWidth,
                    helpText: Ext.String.format('{s name=config/help/connect_attribute}Schreibe die Quell-Id jedes [0]-Produktes in dieses Attribut. Über dieses Attribut kann im Risiko-Managment oder dem Versandkosten-Modul auf [0]-Produkte geprüft werden.{/s}', marketplaceName)
                }
            ]
        });

        return defaultContainer;
    },

    /**
     * Creates the field set items which are displayed in the bottom
     * @return Ext.container.Container
     */
    createBottomElements: function() {
        var me = this;

        var bottomContainer = Ext.create('Ext.container.Container', {
            columnWidth: 1,
            layout: 'anchor',
            border: false,
            items: [
                me.createShippingCostsCombo()
            ]
        });

        return bottomContainer;
    },

    /**
     * Creates the shipping costs page combo
     * @return Ext.container.Container
     */
    createShippingCostsCombo: function () {
        var me = this;
        me.shippingCostsCombo = Ext.create('Shopware.form.field.PagingComboBox', {
            name: 'shippingCostsPage',
            anchor: '100%',
            valueField: 'id',
            displayField: 'name',
            fieldLabel: me.snippets.shippingCostsLabel,
            store: me.staticPagesStore,
            labelWidth: me.defaults.labelWidth,
            helpText: Ext.String.format('{s name=config/help/connect_shipping_costs_page}[0] fügt seine eigenen Versandkosten-Informationen vor ihrer Versandkosten-Seite an. Wählen Sie hier ihre Standard-Versandkostenseite.{/s}', marketplaceName),
            allowBlank: true,
            forceSelection: true,
            beforeBlur: function () {
                var value = this.getRawValue();
                if (value == '') {
                    var model = this.up('form').getRecord();
                    model.set('shippingCostsPage', '');
                    this.lastSelection = [];
                }
                this.doQueryTask.cancel();
                this.assertValue();
            }
        });

        return me.shippingCostsCombo;
    },

    /**
     * Find general config record by id
     * and load into form
     */
    populateForm: function() {
        var me = this,
            record = me.generalConfigStore.getById(me.shopId);

        if (!record) {
            record = Ext.create('Shopware.apps.Connect.model.config.General');
        }


        if (record.get('connectAttribute') < 1) {
            record.set('connectAttribute', 19);
        }

        if (record.get('shippingCostsPage') > 0) {
            me.shippingCostsCombo.valueNotFoundText = record.get('shippingCostsPageName');
        }

        me.loadRecord(record);

    },

    /**
     * Helper method to check if the current
     * shop is default one
     *
     * @returns boolean
     */
    isDefaultShop: function() {
        var me = this;

        return me.defaultShop;
    }
});
//{/block}
