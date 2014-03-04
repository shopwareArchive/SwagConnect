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
    bodyPadding: 20,

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

        me.exportConfigStore = Ext.create('Shopware.apps.Bepado.store.config.Export');
        me.exportConfigStore.load();
        me.exportConfigStore.on('load', function() {
            me.populateForm();
        });

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
            action:'save-export-config',
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
     * Creates the field set items
     * @return Array
     */
    createElements: function () {
        var me = this;

        var descriptionFieldset = Ext.create('Shopware.apps.Bepado.view.config.export.Description')

        var container = Ext.create('Ext.container.Container', {
            columnWidth: 1,
            padding: '0 0 20 0',
            layout: 'fit',
            border: false,
            items: [
                {
                    xtype: 'combobox',
                    fieldLabel: me.snippets.productDescriptionFieldLabel,
                    emptyText: me.snippets.emptyText,
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
                    labelWidth: me.defaults.labelWidth
                }, {
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

        return [ descriptionFieldset, container, priceGrid ];
    },

    /**
     * Creates price grid
     * @return Shopware.apps.Bepado.view.prices.List
     */
    createPriceGrid: function() {
        return Ext.create('Shopware.apps.Bepado.view.prices.List', {
            minHeight: 250
        });
    },

    /**
     * Populate export config form
     */
    populateForm: function() {
        var me = this,
            record = me.exportConfigStore.getAt(0);

        me.loadRecord(record);
    }
});
//{/block}

