/**
 * Extends the article model and adds the customizingId field
 */
/*
 {block name="backend/article_list/model/list/fields" append}
 { name: 'bepado', type: 'boolean' },
 {/block}
 */

/**
 * Extend the article's base fieldSet and add out customizing field
 */
//{block name="backend/article_list/view/main/grid" append}
Ext.define('Shopware.apps.ArticleList.view.main.Grid-Customizing', {
    override: 'Shopware.apps.ArticleList.view.main.Grid',
    infoColumnRenderer: function(value, metaData, record) {
        var me = this,
            title,
            result = me.callOverridden(arguments);

        var style = 'style="width: 25px; height: 25px; display: inline-block; margin-right: 3px;"';

        if (record.get('bepado')) {
            title = 'bepado';
            result = result + '<div  title="' + title + '" class="sprite-share" ' + style + '>&nbsp;</div>';
        }

        console.log(result);

        return result;
    }
});
//{/block}