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
//{block name="backend/bepado/view/config/import/form"}
Ext.define('Shopware.apps.Bepado.view.config.import.Form', {
    extend: 'Ext.form.Panel',
    alias: 'widget.bepado-config-import-form',

    border: false,
    layout: 'anchor',
    autoScroll: true,

    /**
     * Contains the field set defaults.
     */
    defaults: {
        labelWidth: 170,
        anchor: '100%'
    },

    snippets: {
        save: '{s name=config/save}Speichern{/s}',
        cancel: '{s name=config/cancel}Zurücksetzen{/s}',
        importPicturesLabel: '{s name=config/import/pictures_label}Bilder beim Produkt-Erstimport laden{/s}',
        overwritePropertiesLabel: '{s name=config/import/overwrite_properties_label}Folgende Eigenschaften beim Import überschreiben{/s}',
        overwriteProductName: '{s name=config/import/overwrite_product_name}Artikelnamen{/s}',
        overwriteProductPrice: '{s name=config/import/overwrite_product_price}Preise{/s}',
        overwriteProductImages: '{s name=config/import/overwrite_product_images}Bilder{/s}',
        overwriteProductShortDescription: '{s name=config/import/overwrite_product_short_description}Kurzbeschreibung{/s}',
        overwriteProductLongDescription: '{s name=config/import/overwrite_product_long_description}Beim Import Produkt-Langbeschreibungen überschreiben{/s}'
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
     * Creates form elements
     * @return Array
     */
    createElements: function() {

        var me = this,
            configFieldset = me.getConfigFieldset(),
            elements = [];

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
                {
                    xtype: 'checkbox',
                    name: 'importImagesOnFirstImport',
                    fieldLabel: me.snippets.importPicturesLabel,
                    inputValue: 1,
                    uncheckedValue: 0,
                    labelWidth: me.defaults.labelWidth
                }, {
                    xtype      : 'fieldcontainer',
                    fieldLabel : me.snippets.overwritePropertiesLabel,
                    defaultType: 'checkboxfield',
                    labelWidth: me.defaults.labelWidth,
                    items: [
                        {
                            boxLabel  : me.snippets.overwriteProductName,
                            name      : 'overwriteProductName',
                            inputValue: '1',
                            uncheckedValue: 0
                        }, {
                            boxLabel  : me.snippets.overwriteProductPrice,
                            name      : 'overwriteProductPrice',
                            inputValue: 1,
                            uncheckedValue: 0
                        }, {
                            boxLabel  : me.snippets.overwriteProductImages,
                            name      : 'overwriteProductImage',
                            inputValue: 1,
                            uncheckedValue: 0
                        }, {
                            boxLabel  : me.snippets.overwriteProductShortDescription,
                            name      : 'overwriteProductShortDescription',
                            inputValue: 1,
                            uncheckedValue: 0
                        }, {
                            boxLabel  : me.snippets.overwriteProductLongDescription,
                            name      : 'overwriteProductLongDescription',
                            inputValue: 1,
                            uncheckedValue: 0
                        }
                    ]
                }
            ]
        });

        return container;
    }
});
//{/block}

