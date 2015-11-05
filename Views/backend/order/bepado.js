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
    }
});
//{/block}