/*
{block name="backend/product_stream/model/stream/fields" append}
    { name: 'isConnect', type: 'boolean', defaultValue: false },
{/block}
*/

//{block name="backend/product_stream/view/list/list" append}
Ext.define('Shopware.apps.ProductStream.view.list.ConnectList', {
    override: 'Shopware.apps.ProductStream.view.list.List',

    createColumns: function() {
        var me = this,
            newColumns = [],
            columns = me.callOverridden();

        columns.forEach(function(element) {
            if (columns.indexOf(element) == columns.length - 1) {
                newColumns.push(
                    {
                        width: 25,
                        renderer: me.connectColumnRenderer
                    }
                )
            }
            newColumns.push(element)
        });

        return newColumns;
    },

    connectColumnRenderer: function(value, metaData, record) {
        var result;
        var style = 'style="width: 16px; height: 16px; display: inline-block; position: absolute; margin-top: -1px;"';

        if (record.get('isConnect')) {
            result = '<div  title="' + marketplaceName + '" class="connect-icon" ' + style + '>&nbsp;</div>';
        }

        return result;
    }

});
//{/block}