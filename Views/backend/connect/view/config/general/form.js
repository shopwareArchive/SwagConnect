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
        apiKeyDescription: Ext.String.format('{s name=config/api-key-description}Du findest deinen API-Key in [0] unter Einstellungen und Synchronisation.<br><br>{/s}', marketplaceName),
        apiKeyCheck: '{s name=config/api_key_check}Validate{/s}',
        basicSettings: '{s name=config/main/basic_settings}Grundeinstellungen{/s}',
        save: '{s name=config/save}Save{/s}',
        cancel: '{s name=config/cancel}Cancel{/s}',
        detailPageHintLabel: '{s name=config/detail_page_dropshipping_hint}Zeige Dropshipping-Hinweis auf Artikel-Detailseite{/s}',
        noIndexLabel: Ext.String.format('{s name=config/noindex_label}Setze »noindex« meta-tag für [0]-Produkte{/s}', marketplaceName),
        basketHintLabel: '{s name=config/basket_dropshipping_hint_label}Zeige Dropshipping-Hinweis im Warenkorb{/s}',
        connectAttributeLabel: Ext.String.format('{s name=config/connect_attribute_label}[0]-Attribut{/s}', marketplaceName),
        alternativeHostLabel: Ext.String.format('{s name=config/connect_alternative_host}Alternativer [0]-Host (nur für Testzwecke){/s}', marketplaceName),
        shippingCostsLabel: '{s name=config/plus_shipping_costs}Shipping costs page{/s}',
        exportDomainLabel: '{s name=config/alternative_export_url}Alternative export URL{/s}',
        hasSslLabel: '{s name=config/has_ssl_label}My shop has SSL{/s}',
        basicHeader: '{s name=config/main/dropshipping}Dropshipping{/s}',
        unitsHeader: '{s name=navigation/units}Einheiten{/s}',
        unitsFieldsetDescription: Ext.String.format('{s name=config/units/description}Hier ordnen Sie die Einheiten aus Ihrem Shop den Standard-Einheiten in [0] zu.{/s}',marketplaceName),
        importSettingsHeader: '{s name=config/import_settings_header}Import Einstellungen{/s}',
        createCategoriesAutomatically: '{s name=config/import/categories/create_automatically}Kategorien automatisch anlegen{/s}',
        activateProductsAutomatically: '{s name=config/import/products/activate_automatically}Produkte automatisch aktivieren{/s}',
        createUnitsAutomatically: '{s name=config/import/units/create_automatically}Einheiten automatisch anlegen{/s}',
        separateShippingLabel: '{s name=config/separate_shipping_label}Versandkosten als separate Position im Warenkorb ausgeben{/s}',
        advancedHeader: '{s name=config/advanced}Advanced{/s}',
        showDropshippingHintBasketHelptext: '{s name=config/show_dropshipping_hint_basket_helptext}Ein Dropshipping-Hinweis und der Lieferantenname werden angezeigt{/s}',
        showDropshippingHintDetailsHelptext: '{s name=config/show_dropshipping_hint_details_helptext}Ein Dropshipping-Hinweis und der Lieferantenname werden angezeigt{/s}'
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
            advancedConfigFieldset = me.getAdvancedConfigFieldset(),
            elements = [];

        elements.push(me.getBasicFieldset());
        if (defaultMarketplace == false) {
            // extended import settings are available
            // only for SEM shops
            elements.push(me.getImportSettingsFieldset());
        }
        elements.push(basicConfigFieldset);
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
    getBasicFieldset: function() {
        var me = this;

        return Ext.create('Ext.form.FieldSet', {
            columnWidth: 1,
            title: me.snippets.basicSettings,
            defaultType: 'textfield',
            layout: 'anchor',
            items: [
                {
                    xtype: 'checkbox',
                    name: 'hasSsl',
                    fieldLabel: me.snippets.hasSslLabel,
                    inputValue: 1,
                    uncheckedValue: 0,
                    labelWidth: me.defaults.labelWidth,
                    helpText: '{s name=config/help/has_ssl_help_text}If your store has installed SSL certificate please select the checkbox and save your changes. Then verify the API key.{/s}'
                },
                me.createAttributeCombo()
            ]
        });
    },

    /**
     * Creates basic configuration field set
     * @return Ext.form.FieldSet
     */
    getBasicConfigFieldset: function() {
        var me = this,
            items = [],
            leftElements = me.createLeftElements(),
            rightElements = me.createRightElements();

        items.push(leftElements);
        items.push(rightElements);

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

    getApiKeyItems: function () {
        var me = this;

        return [
            {
                xtype: 'container',
                html: me.snippets.apiKeyDescription
            }, {
                xtype: 'container',
                layout: 'hbox',
                columnWidth: 1,
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
                                url: '{url module=backend controller=Connect action=verifyApiKey}',
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
            }
        ];
    },

    /**
     * Creates advanced configuration field set
     * @return Ext.form.FieldSet
     */
    getAdvancedConfigFieldset: function() {
        var me = this,
            items = me.getApiKeyItems();

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

        var fieldset = Ext.create('Ext.form.FieldSet', {
            layout: 'anchor',
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
                    labelWidth: me.defaults.labelWidth,
                    helpText: me.snippets.showDropshippingHintDetailsHelptext
                }, {
                        xtype: 'checkbox',
                        name: 'showShippingCostsSeparately',
                        fieldLabel: me.snippets.separateShippingLabel,
                        labelWidth: me.defaults.labelWidth,
                        inputValue: 1,
                        uncheckedValue: 0
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
                    labelWidth: me.defaults.labelWidth,
                    helpText: me.snippets.showDropshippingHintBasketHelptext
                }
            ]
        });

        return rightContainer;
    },

    /**
     * Creates the Shopware Connect attribute combo field which is displayed only for default shop
     * @return Ext.container.Container
     */
    createAttributeCombo: function() {
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
     * Find general config record by id
     * and load into form
     */
    populateForm: function() {
        var me = this,
            record = me.generalConfigStore.getAt(0);

        if (!record) {
            record = Ext.create('Shopware.apps.Connect.model.config.General');
        }

        if (record.get('connectAttribute') < 1) {
            record.set('connectAttribute', 19);
        }

        me.loadRecord(record);

    }
});
//{/block}
