//{block name="backend/product_stream/view/condition_list/condition_panel" append}
Ext.define('Shopware.apps.ProductStream.view.condition_list.ConnectConditionPanel', {
    override: 'Shopware.apps.ProductStream.view.condition_list.ConditionPanel',

    createConditionHandlers: function() {
        var me = this,
            items = me.callOverridden(arguments);

        items.push(me.createSupplierCondition());
        return items;
    },

    createSupplierCondition: function() {
        return Ext.create('Shopware.apps.ProductStream.view.condition_list.condition.ConnectSupplier')
    }
});
//{/block}