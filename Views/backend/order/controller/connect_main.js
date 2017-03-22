//{namespace name=backend/connect/view/main}
//{block name="backend/order/controller/main" append}
Ext.define('Shopware.apps.Order.controller.ConnectMain', {
    override: 'Shopware.apps.Order.controller.Main',
    snippets: {
        sc_received: '{s name=connect/payment_status/sc_received}Connect received{/s}',
        sc_requested: '{s name=connect/payment_status/sc_requested}Connect requested{/s}',
        sc_initiated: '{s name=connect/payment_status/sc_initiated}Connect initiated{/s}',
        sc_instructed: '{s name=connect/payment_status/sc_instructed}Connect instructed{/s}',
        sc_aborted: '{s name=connect/payment_status/sc_aborted}Connect aborted{/s}',
        sc_timeout: '{s name=connect/payment_status/sc_timeout}Connect timeout{/s}',
        sc_pending: '{s name=connect/payment_status/sc_pending}Connect pending{/s}',
        sc_refunded: '{s name=connect/payment_status/sc_refunded}Connect refunded{/s}',
        sc_verify: '{s name=connect/payment_status/sc_verify}Connect verify{/s}',
        sc_loss: '{s name=connect/payment_status/sc_loss}Connect loss{/s}',
        sc_error: '{s name=connect/payment_status/sc_error}Connect error{/s}'
    },

    getAssociationStores: function (record) {
        var me = this;
        var stores = me.callOverridden(arguments);

        stores["paymentStatusStore"].each(function (record) {
            if (record && me.snippets) {
                var snippet = me.snippets[record.get('name')];
            }
            if (Ext.isString(snippet) && snippet.length > 0) {
                record.set('description', snippet);
            }
        });

        return stores;
    }
});
//{/block}