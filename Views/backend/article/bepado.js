/**
 * Extends the article model and adds the bepado field
 */
/*
 {block name="backend/article/model/attribute/fields" append}
 { name: 'bepadoFixedPrice', type: 'boolean' },
 { name: 'bepadoShopId', type: 'int' },
 {/block}
 */

//{namespace name=backend/bepado/view/main}

/**
 * Extend the article's base fieldSet and add the fixedPrice field
 */
//{block name="backend/article/view/detail/base" append}
Ext.define('Shopware.apps.Article.view.detail.Base-Bepado', {
    override: 'Shopware.apps.Article.view.detail.Base',

    /**
     * @Override: Add a fixedPrice field
     *
     * @returns Array
     */
    createRightElements: function() {
        var me = this,
            result = me.callOverridden(arguments);

        me.bepadoFixedPrice = Ext.create('Ext.form.field.Checkbox', {
            name: 'attribute[bepadoFixedPrice]',
            fieldLabel: '{s name=bepadoFixedPrice}Bepado: Enable price fixing{/s}',
            inputValue: true,
            uncheckedValue:false
        });

        result.push(me.bepadoFixedPrice);

        return result;
    },

    /**
     * Mark the fixedPrice field as readonly if the product is a remote product
     *
     * @param article
     * @param stores
     * @returns Array
     */
    onStoresLoaded: function(article, stores) {
        var me = this,
            attributes;

        if (article && article.getAttribute()) {
            attributes = article.getAttribute().first();

            me.bepadoFixedPrice.setReadOnly(attributes.get('bepadoShopId') > 0);
        }

        return me.callOverridden(arguments);
    }
});
//{/block}

/**
 * Extend the article's price fieldset in order to disable it if the price was configured as fixedPrice in the source shop
 */
//{block name="backend/article/view/detail/prices" append}
Ext.define('Shopware.apps.Article.view.detail.Prices-Bepado', {
    override: 'Shopware.apps.Article.view.detail.Prices',

    createElements: function() {
        var me = this,
            attributes,
            style,
            label,
            tabPanel;


        tabPanel =  me.callOverridden(arguments);

        if (me.article && me.article.getAttribute()) {
            attributes = me.article.getAttribute().first();

            style = 'style="width: 25px; height: 25px; display: inline-block; margin-right: 3px;"';

            if(attributes.get('bepadoShopId') > 0 && attributes.get('bepadoFixedPrice')) {
                label = { xtype: 'label', html: '<div title="" class="bepado-icon" ' +  style + '>&nbsp;</div>{s name="bepadoFixedPriceMessage"}The supplier of this product has enabled the fixed price feature. For this reason you will not be able to edit the price.{/s}' };


                tabPanel.setDisabled(true);

                return [
                    label,
                    tabPanel
                ];
            }
        }

        return result;

    }
});
//{/block}