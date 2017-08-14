/**
 * Extends the article model and adds the customizingId field
 */
/*
 {block name="backend/order/model/order/fields" append}
 { name: 'connectOrderId', type: 'auto', useNull : true },
 { name: 'connectShopId', type: 'int', useNull : true },
 { name: 'connectShop', type: 'string', useNull : true },
 {/block}
 */

/**
 * Extend the article's base fieldSet and add out customizing field
 */
//{namespace name=backend/connect/view/main}
//{block name="backend/order/view/list/list" append}
Ext.define('Shopware.apps.Order.view.list.List-Customizing', {
    override: 'Shopware.apps.Order.view.list.List',

    /**
     * @Override
     * @returns Array
     */
    getColumns: function() {
        var me = this,
            columns = me.callOverridden(arguments);

        columns.push({
            header: '',
            dataIndex: 'connectOrderId',
            width:30,
            renderer: function(value, metaData, record) {
                var me = this,
                    title,
                    result = '';

                var style = 'style="width: 25px; height: 25px; display: inline-block; margin-right: 3px;"';

                if (value) {
                    if (value == 'remote') {
                        var orderDescription = Ext.String.format('{s name=order/fromRemote}Diese Bestellung enthält [0]-Produkte eines Fremdshops{/s}', marketplaceName);
                        result = '<div  title="" class="connect-icon" ' + style + '>&nbsp;</div>';
                        metaData.tdAttr = 'data-qtip="' + orderDescription + '"';
                    } else {
                        result = '<div  title="" class="connect-icon-green" ' + style + '>&nbsp;</div>';
                        metaData.tdAttr = 'data-qtip="' + value + ' / ' +  record.get('connectShop') + '"';
                    }
                }

                return result;
            }
        });

        return columns;
    },

    /**
     * Column renderer function for the payment column of the list grid.
     * @Override
     * @param [string] value    - The field value
     * @param [string] metaData - The model meta data
     * @param [string] record   - The whole data model
     */
    shopColumn: function(value, metaData, record) {
        var me = this;

        if (record.data.connectShop) {
            return record.data.connectShop;
        }

        return me.callOverridden(arguments);
    }
});
//{/block}

//{block name="backend/order/view/detail/overview" append}
Ext.define('Shopware.apps.Order.view.detail.Overview-Customizing', {
    override: 'Shopware.apps.Order.view.detail.Overview',

    connectSnippets: {
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
    initComponent:function () {
        var me = this;

        me.paymentStatusStore.each(function (record) {
            if (record && me.connectSnippets) {
                var snippet = me.connectSnippets[record.get('name')];
            }
            if (Ext.isString(snippet) && snippet.length > 0) {
                record.set('description', snippet);
            }
        });

        return me.callOverridden(arguments);
    }
});
//{/block}