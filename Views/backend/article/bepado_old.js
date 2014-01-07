/**
 * Extends the article model and adds the bepado field
 */
/*
 {block name="backend/article/model/attribute/fields" append}
 { name: 'bepadoFixedPrice', type: 'boolean' },
 { name: 'bepadoShopId', type: 'int', useNull: true },
 { name: 'bepadoUpdatePrice', type: 'string', useNull: true  },
 { name: 'bepadoUpdateImage', type: 'string', useNull: true  },
 { name: 'bepadoUpdateLongDescription', type: 'string', useNull: true  },
 { name: 'bepadoUpdateShortDescription', type: 'string', useNull: true  },
 { name: 'bepadoUpdateName', type: 'string', useNull: true  },
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

            me.up('window').bepadoFixedPrice.setReadOnly(attributes.get('bepadoShopId') > 0);
        }

        return me.callOverridden(arguments);
    }
});
//{/block}

/**
 * Disable the shippingFree field for bepado products
 */
//{block name="backend/article/view/detail/settings" append}
Ext.define('Shopware.apps.Article.view.detail.Settings-Bepado', {
    override: 'Shopware.apps.Article.view.detail.Settings',

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

            field = me.up('window').down('article-settings-field-set').down('checkboxfield[fieldLabel=' + me.snippets.shippingFree.field + ']');

            if (field) {
                field.setReadOnly(attributes.get('bepadoShopId') > 0);
            }
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

        return tabPanel;

    }
});
//{/block}

