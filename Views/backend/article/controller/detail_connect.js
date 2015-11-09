//{block name="backend/article/controller/detail" append}
Ext.define('Shopware.apps.Article.controller.DetailConnect', {
    override: 'Shopware.apps.Article.controller.Detail',

    /**
     * @Override
     * This method is called after an article was saved successfully.
     *
     * Set the articleId to the store and enable the tab then
     */
    reconfigureAssociationComponents: function() {
        var me = this,
            connectController;

        me.callOverridden(arguments);

        connectController = me.subApplication.getController('Connect');

        connectController.connectStore.getProxy().extraParams.articleId = me.subApplication.article.get('id');
        connectController.doReloadConnectStore();
        connectController.enableTab();

    },


    /**
     * @Override clonePrices in order to enforce the price attributes
     *
     * @param arguments
     */
    clonePrices: function() {
        var me = this,
            prices = me.callParent(arguments),
            firstGroupPrices = arguments[0],
            length = prices.length,
            originalPrice;

        if ('{$disableConnectPrice}' == 'true') {
            return prices;
        }


        // Make sure that the copy also has a copy of the price attributes
        for(var i=0; i<length; i++) {
            originalPrice = firstGroupPrices[i];
            prices[i].getAttributesStore = Ext.clone(originalPrice.getAttributes());
        }

        return prices;
    }


});
//{/block}


