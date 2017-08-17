//{namespace name=backend/connect/view/main}

//{block name="backend/shipping/model/attribute"}
//{$smarty.block.parent}
//{/block}

//{block name="backend/shipping/model/attribute/fields" append}
   { name: 'connectAllowed', type: 'int' },
//{/block}

/**
 * Show a connect checkbox in the 'advanced' menu
 */
//{block name="backend/shipping/view/edit/advanced" append}
Ext.define('Shopware.apps.Shipping.view.edit.Advanced-Connect', {
    override: 'Shopware.apps.Shipping.view.edit.Advanced',

    /**
     * @Override
     * @returns Array
     */
    getFormElementsRight: function() {
        var me = this,
            items = me.callOverridden(arguments);

        // connectAllowed is not used during checkout
        //items.push({
        //    xtype : 'checkbox',
        //    name : 'attribute[connectAllowed]',
        //    internalName: 'connect',
        //    fieldLabel : '{s name=shipping/connectAllowed}Allow with connect{/s}'
        //});

        return items;
    }


});

//{/block}

//{block name="backend/shipping/controller/default_form" append}
Ext.define('Shopware.apps.Shipping.controller.DefaultForm-Connect', {
    override: 'Shopware.apps.Shipping.controller.DefaultForm',

    /**
     * As the attributes are not persisted automatically, they are saved after everything else has been saved
     *
     * @Override
     *
     * @returns Object
     */
    onDispatchSave: function() {
        var me = this,
            result = me.callOverridden(arguments),
            advancedForm = me.getAdvancedForm(),
            connectAllowed = advancedForm.down('checkbox[internalName=connect]').getValue();

        me.saveConnectAttribute(advancedForm.record.get('id'), connectAllowed);

        return result;
    },

    /**
     * Save the given connect value via an Ajax request
     *
     * @param shippingId
     * @param attributeValue
     */
    saveConnectAttribute: function(shippingId, attributeValue) {
        Ext.Ajax.request({
            url: '{url controller=connect action=saveShippingAttribute}',
            method: 'POST',
            params: {
                shippingId: shippingId,
                connectAllowed: attributeValue ? 1 : 0
            },
            failure: function(response, opts) {
                Shopware.Notification.createGrowlMessage('{s name=connect/error}Error{/s}', response.responseText);
            }

        });
    }

});
//{/block}