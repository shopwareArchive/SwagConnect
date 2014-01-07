/**
 * Extend the article's price fieldset in order to disable it if the price was configured as fixedPrice in the source shop
 */
//{block name="backend/article/view/detail/prices" append}
Ext.define('Shopware.apps.Article.view.detail.PricesBepado', {
    override: 'Shopware.apps.Article.view.detail.Prices',

    createElements: function() {
        var me = this,
            attributes,
            style,
            label,
            tabPanel;


        tabPanel =  me.callOverridden(arguments);


        style = 'style="width: 25px; height: 25px; display: inline-block; margin-right: 3px;"';

        me.bepadoLabel = Ext.create('Ext.form.Label', {
            hidden: true,
            html: '<div title="" class="bepado-icon" ' + style + '>&nbsp;</div>{s name="bepadoFixedPriceMessage"}The supplier of this product has enabled the fixed price feature. For this reason you will not be able to edit the price.{/s}'
        });

        return [
            me.bepadoLabel,
            tabPanel
        ];

    }
});
//{/block}

