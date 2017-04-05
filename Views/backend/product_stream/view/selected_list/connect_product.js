//{namespace name=backend/connect/view/main}
//{block name="backend/product_stream/view/selected_list/product" append}
Ext.define('Shopware.apps.ProductStream.view.selected_list.ConnectProduct', {
    override: 'Shopware.apps.ProductStream.view.selected_list.Product',

    snippets: {
        title: '{s name=product_stream/stream_title}Product stream sync failed{/s}',
        hasManyVariants: '{s name=product_stream/has_many_variants_message}Product [0] has too many variants. You need to sync it manually from Connect export{/s}'
    },

    addRecord: function (record) {
        var me = this;
        me.callOverridden(arguments);
        me.hasManyVariants(record);
    },

    hasManyVariants: function(record) {
        var me = this;
        this.sendAjaxRequest(
            '{url controller=Connect action=hasManyVariants}',
            { streamId: this.streamId, articleId: record.get('id') },
            function (response){
                if (response.hasManyVariants) {
                    var message = Ext.String.format(me.snippets.hasManyVariants, record.get('name'));

                    Shopware.Notification.createGrowlMessage(
                        me.snippets.title,
                        message
                    );
                }
            }
        );
    }
});
//{/block}