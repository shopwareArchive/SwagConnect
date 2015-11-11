/**
 * Extends the article model and adds the customizingId field
 */
/*
 {block name="backend/article_list/model/list/fields" append}
 { name: 'connect', type: 'boolean' },
 {/block}
 */

/**
 * Extend the article list in order to show a connect icon and disable the price field
 */
//{block name="backend/article_list/view/main/grid" append}
Ext.define('Shopware.apps.ArticleList.view.main.Grid-Customizing', {
    override: 'Shopware.apps.ArticleList.view.main.Grid',

    /**
     * @Override the init method in order to disable the editor for price field of connect products
     */
    initComponent: function() {
        var me = this;

        me.callOverridden(arguments);

        var editor;
        if (me.rowEditing) {
            editor = me.rowEditing;
        } else {
            editor = me.editor;
        }

        editor.on('beforeedit', function(editor, context, eOpts) {
            var me = this;

            if (!me.columns || !context.record) {
                return;
            }

            if (me.hasOwnProperty('priceColumn')) {
                me.getPriceColumn();
            }

            // Disable the price column for connect products
            if (me.hasOwnProperty('priceColumn')) {
                me.priceColumn.getEditor().setDisabled(context.record.get('connect'));
            }
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
     * @Override: Show a connect icon for connect products
     *
     * @param value
     * @param metaData
     * @param record
     * @returns Object
     */
    infoColumnRenderer: function(value, metaData, record) {
        var me = this,
            result = me.callOverridden(arguments);

        var style = 'style="width: 25px; height: 25px; display: inline-block; margin-right: 3px;"';

        if (record.get('connect')) {
            result = result + '<div  title="' + marketplaceName + '" class="connect-icon" ' + style + '>&nbsp;</div>';
        }

        return result;
    }

});
//{/block}

/**
 * Add a connect to filter to allow the user to only show (imported) connect products
 */
//{block name="backend/article_list/view/main/window" append}
Ext.define('Shopware.apps.ArticleList.view.main.Window-Connect', {
    override: 'Shopware.apps.ArticleList.view.main.Window',

    /**
     * @Override
     */
    createFilterPanel: function() {
        var me = this,
            panel = me.callOverridden(arguments),
            radioGroup;

        try {
            radioGroup = panel.items.items[0];
            radioGroup.add(
                Ext.create('Ext.form.field.Radio', { boxLabel: marketplaceName, name: 'filter', inputValue: 'connect' })
            );
        }catch(e) {
            return panel;
        }

        return panel;
    }
});
//{/block}
