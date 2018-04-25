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
        anchor: '100%'
    },

    snippets: {
        apiKeyHeader: '{s name=config/main/api_key}API-Key{/s}',
        apiKeyDescription: Ext.String.format('{s name=config/api-key-description}Du findest deinen API-Key in [0] unter Einstellungen und Synchronisation. <br> ' +
            'Durch das Prüfen der Verbindung übermitteln sie ihre Shop URL an Connect<br><br>{/s}', marketplaceName),
        apiKeyCheck: '{s name=config/api_key_check}Validate{/s}',
        basicSettings: '{s name=config/main/basic_settings}Grundeinstellungen{/s}',
        save: '{s name=config/save}Save{/s}',
        cancel: '{s name=config/cancel}Cancel{/s}',
        noIndexLabel: Ext.String.format('{s name=config/noindex_label}Setze »noindex« meta-tag für [0]-Produkte{/s}', marketplaceName),
        shippingCostsLabel: '{s name=config/plus_shipping_costs}Shipping costs page{/s}',
        exportDomainLabel: '{s name=config/alternative_export_url}Alternative export URL{/s}',
        unitsHeader: '{s name=navigation/units}Einheiten{/s}',
        unitsFieldsetDescription: Ext.String.format('{s name=config/units/description}Hier ordnen Sie die Einheiten aus Ihrem Shop den Standard-Einheiten in [0] zu.{/s}', marketplaceName),
        advancedHeader: '{s name=config/advanced}Advanced{/s}',
        resetBtn: '{s name=config/reset_btn}reset{/s}',
        priceResetLabel: '{s name=config/price_reset_label}Reset exported prices{/s}',
        priceResetMessage: '{s name=config/price_reset_message}Your exported products will be deleted in Connect and your sent offers will be invalid. Do you want to continue?{/s}',
        exchangeSettingResetLabel: '{s name=config/exchange_settings_label}Reset exchange settings{/s}',
        exchangeSettingResetMessage: '{s name=config/exchange_settings_message}Your exported products will be deleted in Connect and your sent offers will be invalid. Do you want to continue?{/s}'
    },

    initComponent: function () {
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
            callback: function () {
                me.populateForm();
            }
        });

        me.callParent(arguments);
    },

    /**
     * Creates form elements
     * @return Array
     */
    createElements: function () {
        var me = this,
            elements = [];

        elements.push(me.getAdvancedConfigFieldset());

        return elements;
    },

    /**
     * Returns form buttons, save and cancel
     * @returns Array
     */
    getFormButtons: function () {
        var me = this;

        return [
            '->',
            Ext.create('Ext.button.Button', {
                text: me.snippets.cancel,
                handler: function (btn) {
                    btn.up('window').close();
                }
            }),
            Ext.create('Ext.button.Button', {
                text: me.snippets.save,
                action: 'save-general-config',
                cls: 'primary'
            })
        ];
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
                        handler: function (btn) {
                            var apiField = btn.up('form').down('textfield[name=apiKey]'),
                                apiKey = apiField.getValue();
                            Ext.Ajax.request({
                                scope: this,
                                url: '{url module=backend controller=Connect action=verifyApiKey}',
                                success: function (result, request) {
                                    var response = Ext.JSON.decode(result.responseText);
                                    Ext.get(apiField.inputEl).setStyle('background-color', response.success ? '#C7F5AA' : '#FFB0AD');
                                    if (response.message) {
                                        Shopware.Notification.createGrowlMessage(
                                            btn.title,
                                            response.message
                                        );
                                    }
                                },
                                failure: function () {

                                },
                                params: {
                                    apiKey: apiKey
                                }
                            });
                        }
                    }
                ]
            }
        ];
    },

    registerEvents: function() {
        this.addEvents('resetPriceType');
    },

    /**
     *
     */
    getResetPriceTypeFields: function () {
        var me = this;

        return Ext.create('Ext.container.Container', {
            layout: 'column',
            columnWidth: 1,
            margin: '0 0 10 0',
            items: [
                {
                    xtype: 'container',
                    margin: '10 30 0 0',
                    width: 185,
                    style: {
                        'color': '#475c6a',
                        'font-weight': 'bold',
                        'font-size': '11px'
                    },
                    html: me.snippets.priceResetLabel
                }, {
                    xtype: 'button',
                    flex: 1,
                    height: 27,
                    width: 100,
                    text: me.snippets.resetBtn,
                    handler: function (btn) {
                        Ext.MessageBox.confirm(me.snippets.priceResetLabel, me.snippets.priceResetMessage, function (response) {
                            if (response !== 'yes') {
                                return false;
                            }

                            me.fireEvent('resetPriceType');
                        });
                    }
                }
            ]
        });
    },


    getResetExchangeSettingFields: function () {
        var me = this;

        return Ext.create('Ext.container.Container', {
            layout: 'column',
            columnWidth: 2,
            items: [
                {
                    xtype: 'container',
                    cls: 'sc-exchange-label-position',
                    width: 185,
                    html: me.snippets.exchangeSettingResetLabel
                }, {
                    xtype: 'button',
                    flex: 1,
                    height: 27,
                    width: 100,
                    text: me.snippets.resetBtn,
                    handler: function (btn) {
                        Ext.MessageBox.confirm(me.snippets.exchangeSettingResetLabel, me.snippets.exchangeSettingResetMessage, function (response) {
                            if (response !== 'yes') {
                                return false;
                            }

                            me.fireEvent('resetExchangeSettings');
                        });
                    }
                }
            ]
        });
    },

    /**
     * Creates advanced configuration field set
     * @return Ext.form.FieldSet
     */
    getAdvancedConfigFieldset: function () {
        var me = this,
            items = me.getApiKeyItems();

        me.shopwareIdField = Ext.create('Ext.form.field.Display', {
            fieldLabel: 'Shopware ID',
            name: 'shopwareId'
        });

        items.push(Ext.create('Ext.container.Container', {
            columnWidth: 0.5,
            layout: 'anchor',
            border: false,
            items: [
                {
                    xtype: 'displayfield',
                    fieldLabel: 'Shop ID',
                    name: 'shopId'
                },
                me.shopwareIdField,
                {
                    xtype: 'checkbox',
                    name: 'detailProductNoIndex',
                    fieldLabel: me.snippets.noIndexLabel,
                    inputValue: 1,
                    uncheckedValue: 0,
                    labelWidth: me.defaults.labelWidth
                }
            ]
        }));

        items.push(me.getResetPriceTypeFields());
        items.push(me.getResetExchangeSettingFields());

        return Ext.create('Ext.form.FieldSet', {
            layout: 'anchor',
            title: me.snippets.advancedHeader,
            defaults: {
                labelWidth: 170,
                anchor: '100%'
            },
            items: items
        });
    },

    /**
     * Find general config record by id
     * and load into form
     */
    populateForm: function () {
        var me = this,
            record = me.generalConfigStore.getAt(0);

        if (!record) {
            record = Ext.create('Shopware.apps.Connect.model.config.General');
        }

        if (!record.get('shopwareId')) {
            me.shopwareIdField.hide();
        }

        me.loadRecord(record);
    }
});
//{/block}
