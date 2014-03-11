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
//{block name="backend/bepado/view/config/general/form"}
Ext.define('Shopware.apps.Bepado.view.config.general.Form', {
    extend: 'Ext.form.Panel',
    alias: 'widget.bepado-config-form',

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
        anchor: '100%'
    },

    snippets: {
        apiKeyHeader: '{s name=config/api_key}API-Key{/s}',
        apiKeyDescription: '{s name=config/api_key_description}Your bepado API key{/s}',
        apiKeyCheck: '{s name=config/api_key_check}Validate{/s}',
        save: '{s name=config/save}Save{/s}',
        cancel: '{s name=config/cancel}Cancel{/s}',
        cloudSearchLabel: '{s name=config/cloud_search_label}Enable cloud search{/s}',
        detailPageHintLabel: '{s name=config/details_page_hint}Show marketplace hint on article detail page{/s}',
        noIndexLabel: '{s name=config/noindex_label}Set noindex meta tag for bepado products{/s}',
        basketHintLabel: '{s name=config/basket_hint_label}Show marketplace hint in basket{/s}',
        bepadoAttributeLabel: '{s name=config/bepado_attribute_label}bepado attribute{/s}',
        alternativeHostLabel: '{s name=config/bepado_alternative_host}Alternate bepado host (for testing purpose){/s}',
        logLabel: '{s name=config/log_label}Log bepado requests{/s}',
        logDescription: '{s name=config/log_description}Will write all bepado requests to log{/s}',
        shippingCostsLabel: '{s name=config/plus_shipping_costs}Shipping costs page{/s}'
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

        me.generalConfigStore = Ext.create('Shopware.apps.Bepado.store.config.General').load({
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
            apiFieldset = me.getApiKeyFieldset(),
            configFieldset = me.getConfigFieldset(),
            elements = [];

        if (me.defaultShop) {
            elements.push(apiFieldset);
        }
        elements.push(configFieldset);

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
                            padding: '0 20 0 0'
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
                                    url: window.location.pathname + 'bepado/verifyApiKey',
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
            ]
        });

        return apiFieldset;
    },

    /**
     * Creates the field set which displayed
     * @return Ext.form.FieldSet
     */
    getConfigFieldset: function() {
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
            border: false,
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
                    name: 'cloudSearch',
                    fieldLabel: me.snippets.cloudSearchLabel,
                    inputValue: 1,
                    uncheckedValue: 0,
                    labelWidth: me.defaults.labelWidth
                }, {
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
                    name: 'detailProductNoIndex',
                    fieldLabel: me.snippets.noIndexLabel,
                    inputValue: 1,
                    uncheckedValue: 0,
                    labelWidth: me.defaults.labelWidth
                }, {
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
                    name: 'bepadoAttribute',
                    anchor: '100%',
                    required: true,
                    editable: false,
                    valueField: 'id',
                    displayField: 'name',
                    value: 19,
                    fieldLabel: me.snippets.bepadoAttributeLabel,
                    store: attributeStore,
                    labelWidth: me.defaults.labelWidth,
                    helpText: '{s name=config/help/bepado_attribute}Write the source id of each bepado product to this attribute. So you can check for bepado products in risk managment or the shipping cost module by using this attribute.{/s}',
                }, {
                    xtype: 'textfield',
                    name: 'bepadoDebugHost',
                    anchor: '100%',
                    fieldLabel: me.snippets.alternativeHostLabel,
                    labelWidth: me.defaults.labelWidth,
                    helpText: '{s name=config/help/debug_host}Use the given host instead of the official bepado host - only for development purpose{/s}',
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
                {
                    xtype: 'pagingcombo',
                    name: 'shippingCostsPage',
                    anchor: '100%',
                    required: false,
                    editable: false,
                    valueField: 'id',
                    displayField: 'name',
                    fieldLabel: me.snippets.shippingCostsLabel,
                    store: me.staticPagesStore,
                    labelWidth: me.defaults.labelWidth,
                    helpText: '{s name=config/help/bepado_shipping_costs_page}Select which page to display in the detail page of the bepado products.{/s}',
                    forceSelection: true,
                    triggerAction: 'all'
                }
            ]
        });

        return bottomContainer;
    },

    /**
     * Find general config record by id
     * and load into form
     */
    populateForm: function() {
        var me = this,
            record = me.generalConfigStore.getById(me.shopId);

        me.loadRecord(record);
    }
});
//{/block}
