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
//{block name="backend/bepado/view/shipping_groups/delete_group"}
Ext.define('Shopware.apps.Bepado.view.config.shippingGroups.DeleteGroup', {
    extend: 'Ext.window.Window',
    alias: 'widget.bepado-shipping-add-group',

    layout: 'fit',
    width: 500,
    height:'30%',
    modal: true,
    title: '{s name=config/shipping_groups/delete_group}Delete group{/s}',

    initComponent: function() {
        var me = this;

        Ext.applyIf(me, {
            items: [ me.getForm(),
                me.getButtons()]
        });

        me.callParent(arguments);
    },

    /**
     * Returns generated shipping group form
     */
    getForm: function() {
        var me = this;

        return {
            xtype: 'form',
            url: '{url controller=ShippingGroups action=deleteShippingGroup}',
            layout: 'anchor',
            bodyPadding: 10,
            defaults: {
                anchor: '100%'
            },
            items: [ me.getShippingGroupCombo() ]
            ,
            buttons: [ me.getButtons() ]
        };
    },

    /**
     * Creates save bottom buttons
     * @returns string
     */
    getButtons: function() {
        var me = this;
        return {
            text: '{s name=config/shipping_groups/delete}Delete{/s}',
            cls: 'primary',
            formBind: true,
            disabled: true,
            handler: function() {
                var form = this.up('form').getForm();
                if (form.isValid()) {
                    Ext.MessageBox.confirm(
                        '{s name=config/shipping_groups/confirm_delete}Delete group?{/s}',
                        '{s name=config/shipping_groups/confirm_delete_message}All affected products will be updated. Continue?{/s}',
                        function (response) {
                            if ( response === 'yes' ) {
                                var grid = Ext.getCmp('bepado-shipping-groups-list');
                                form.submit({
                                    success: function(form, action) {
                                        Shopware.Notification.createGrowlMessage('{s name=success}Success{/s}','{s name=config/shipping_groups/group_deleted}Group has been deleted.{/s}');
                                        me.close();
                                        grid.getStore().load();
                                    },
                                    failure: function(form, action) {
                                        var response = Ext.decode(action.response.responseText);
                                        Shopware.Notification.createGrowlMessage('{s name=error}Error{/s}', response.message);
                                    }
                                });
                            }
                        }
                    );
                }
            }
        };
    },

    getShippingGroupCombo: function() {
        var me = this;

        return me.bepadoShippingGroup = Ext.create('Ext.form.ComboBox', {
            labelWidth: 155,
            name: 'groupName',
            fieldLabel: '{s name=config/shipping_groups/group_name}Group name{/s}',
            store: me.getShippingGroupStore(),
            displayField: 'groupName',
            valueField: 'groupName',
            allowBlank: false
        })
    },

    getShippingGroupStore: function() {
        var me = this;
        if (!me.shippingGroupStore) {
            me.shippingGroupStore = Ext.create('Shopware.apps.Bepado.store.shippingGroup.Groups').load();
        }

        return me.shippingGroupStore;
}
});
//{/block}