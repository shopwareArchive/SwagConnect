//{namespace name=backend/bepado/view/main}

/**
 * In old SW versions we need to overwrite the whole shipping attribute module
  */
//{block name="backend/shipping/model/attribute"}
//{if $useOldBepadoShippingAttributeExtension}

Ext.define('Shopware.apps.Shipping.model.Attribute', {
    extend: 'Ext.data.Model',

    fields: [
        { name: 'id', type: 'int' },
        { name: 'dispatchId', type: 'int', useNull: true },
        { name: 'bepadoAllowed', type: 'int' }
    ]
});
//{else}
//    {$smarty.block.parent}
//{/if}
//{/block}

/**
 * This block does not exist before 4.2.0
 */
//{block name="backend/shipping/model/attribute/fields"}
//   { name : 'bepadoAllowed', type : 'int' },
//{/block}

/**
 * Show a bepado checkbox in the 'advanced' menu
 */
//{block name="backend/shipping/view/edit/advanced" append}
Ext.define('Shopware.apps.Shipping.view.edit.Advanced-Bepado', {
    override: 'Shopware.apps.Shipping.view.edit.Advanced',

    /**
     * @Override
     * @returns Array
     */
    getFormElementsRight: function() {
        var me = this,
            items = me.callOverridden(arguments);

        items.push({
            xtype : 'checkbox',
            name : 'attribute[bepadoAllowed]',
            internalName: 'bepado',
            fieldLabel : '{s name=shipping/bepadoAllowed}Allow with bepado{/s}'
        });

        return items;
    }


});

//{/block}

//{block name="backend/shipping/controller/default_form" append}
Ext.define('Shopware.apps.Shipping.controller.DefaultForm-Bepado', {
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
            bepadoAllowed = advancedForm.down('checkbox[internalName=bepado]').getValue();

        me.saveBepadoAttribute(advancedForm.record.get('id'), bepadoAllowed);

        return result;
    },

    /**
     * Save the given bepado value via an Ajax request
     *
     * @param shippingId
     * @param attributeValue
     */
    saveBepadoAttribute: function(shippingId, attributeValue) {
        Ext.Ajax.request({
            url: '{url controller=bepado action=saveShippingAttribute}',
            method: 'POST',
            params: {
                shippingId: shippingId,
                bepadoAllowed: attributeValue ? 1 : 0
            },
            failure: function(response, opts) {
                Shopware.Notification.createGrowlMessage('{s name=error}Error{/s}', response.responseText);
            }

        });
    }

});
//{/block}