/**
 * Extends the article model and adds the customizingId field
 */
/*
 {block name="backend/article_list/model/list/fields" append}
 { name: 'bepado', type: 'boolean' },
 {/block}
 */

/**
 * Extend the article list in order to show a bepado icon and disable the price field
 */
//{block name="backend/article_list/view/main/grid" append}
Ext.define('Shopware.apps.ArticleList.view.main.Grid-Customizing', {
    override: 'Shopware.apps.ArticleList.view.main.Grid',

    /**
     * @Override the init method in order to disable the editor for price field of bepado products
     */
    initComponent: function() {
        var me = this;

        me.callOverridden(arguments);

        me.editor.on('beforeedit', function(editor, context, eOpts) {
            var me = this;

            if (!me.columns || !context.record) {
                return;
            }

            if (!me.priceColumn) {
                me.getPriceColumn();
            }

            // Disable the price column for bepado products
            me.priceColumn.getEditor().setDisabled(context.record.get('bepado'));
        }, me);
    },

    /**
     * Helper to get the price column
     */
    getPriceColumn: function() {
        var me = this,
            i, currentColumn, columnsLength = me.columns.length;

        // Save the default price editor and the price column
        for (i = 0; i<columnsLength; i++) {
            currentColumn =me.columns[i];

            if (currentColumn.dataIndex == 'price') {
                me.priceColumn = currentColumn;
            }
        }
    },

    /**
     *
     * @Override: Show a bepado icon for bepado products
     *
     * @param value
     * @param metaData
     * @param record
     * @returns Object
     */
    infoColumnRenderer: function(value, metaData, record) {
        var me = this,
            title,
            result = me.callOverridden(arguments);

        var style = 'style="width: 25px; height: 25px; display: inline-block; margin-right: 3px;"';

        if (record.get('bepado')) {
            title = 'bepado';
            result = result + '<div  title="' + title + '" class="bepado-icon" ' + style + '>&nbsp;</div>';
        }

        return result;
    }

});
//{/block}