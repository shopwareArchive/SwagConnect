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

    /**
     * Contains the field set defaults.
     */
    defaults: {
        labelWidth: 200,
        anchor: '100%'
    },

    snippets: {
        save: '{s name=config/save}Speichern{/s}',
        cancel: '{s name=config/cancel}Zurücksetzen{/s}',
        productDescriptionFieldLabel: '{s name=config/export/product_description_field_label}Produkt-Beschreibungsfeld{/s}',
        autoProductSync: '{s name=config/export/auto_product_sync_label}Geänderte Produkte automatisch mit bepoado synchronisieren{/s}',
        autoPlayedChanges: '{s name=config/export/changes_auto_played_label}Änderungen werden automatisch auf bepado gespielt{/s}',
        emptyText: '{s name=config/export/empty_text_combo}Bitte wählen{/s}'
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

        me.callParent(arguments);
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
            action:'save-import-config',
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
     * Creates the field set
     * @return Ext.form.FieldSet
     */
    getConfigFieldset: function() {
        var me = this,
            items = [],
            elements = me.createElements();

        items.push(elements);

        var fieldset = Ext.create('Ext.form.FieldSet', {
            layout: 'column',
            border: false,
            items: items
        });

        return fieldset;
    },

    /**
     * Creates the field set items
     * @return Ext.container.Container
     */
    createElements: function () {
        var me = this;

        var container = Ext.create('Ext.container.Container', {
            columnWidth: 1,
            padding: '0 20 0 0',
            layout: 'anchor',
            border: false,
            items: [
                Ext.create('Ext.form.ComboBox', {
                    fieldLabel: me.snippets.productDescriptionFieldLabel,
                    emptyText: 'Bitte wählen',
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
                    valueField: 'value'
                }), {
                    xtype      : 'fieldcontainer',
                    fieldLabel : me.snippets.autoProductSync,
                    defaultType: 'checkboxfield',
                    labelWidth: me.defaults.labelWidth,
                    items: [
                        {
                            boxLabel  : me.snippets.autoPlayedChanges,
                            name      : 'autoUpdateProducts',
                            inputValue: 1,
                            uncheckedValue: 0
                        }
                    ]
                }
            ]
        });

        var priceGrid = me.createPriceGrid();

        return [ container, priceGrid ];
    },

    createPriceGrid: function() {
        return Ext.create('Shopware.apps.Bepado.view.prices.List', {
            minHeight: 250
        });
    }
});
//{/block}

