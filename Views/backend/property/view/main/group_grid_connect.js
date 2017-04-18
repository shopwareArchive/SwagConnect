//{namespace name=backend/connect/view/main}
//{block name="backend/Property/view/main/group_grid" append}
Ext.define('Shopware.apps.Property.view.main.GroupGridConnect', {
    override: 'Shopware.apps.Property.view.main.GroupGrid',

    getColumns: function() {
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

        if (record.get('connect')) {
            result = '<div  title="' + marketplaceName + '" class="connect-icon sc-icon-position">&nbsp;</div>';
        }

        return result;
    }

});
//{/block}