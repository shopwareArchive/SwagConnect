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

    snippets: {
        apiKeyHeader: '{s name=config/api_key}API-Key{/s}',
        apiKeyDescription: '{s name=config/api_key_description}{/s}',
        apiKeyCheck: '{s name=config/api_key_check}Überprüfen{/s}',
        save: '{s name=cofig/save}Speichern{/s}',
        cancel: '{s name=config/cancel}Zurücksetzen{/s}'
    },

    initComponent: function() {
        var me = this;

        me.items = me.createElements(me.defaultShop);
        me.dockedItems = [{
                xtype: 'toolbar',
                dock: 'bottom',
                ui: 'shopware-ui',
                cls: 'shopware-toolbar',
                items: me.getFormButtons()
            }];

        me.callParent(arguments);
    },

    /**
     * Creates form elements
     * @param isDefaultShop
     * @return Array
     */
    createElements: function(isDefaultShop) {
        var me = this,
            apiFieldset = me.getApiKeyFieldset(),
            elements = [];

        if (isDefaultShop) {
            elements.push(apiFieldset);
        }

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
            action:'save-config',
            cls:'primary'
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
            items :[
                {
                    xtype: 'container',
                    html: me.snippets.apiKeyDescription
                }, {
                    fieldLabel: me.snippets.apiKeyHeader,
                    labelWidth: 200,
                    name: 'apiKey'
                }, {
                    xtype: 'button',
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
        });

        return apiFieldset;
    }
});
//{/block}
