//{block name="backend/article/controller/detail" append}
Ext.define('Shopware.apps.Article.controller.DetailBepado', {
    override: 'Shopware.apps.Article.controller.Detail',

    /**
     * @Override
     * This method is called after an article was saved successfully.
     *
     * Set the articleId to the store and enable the tab then
     */
    reconfigureAssociationComponents: function() {
        var me = this,
            bepadoController;

        me.callOverridden(arguments);

        bepadoController = me.subApplication.getController('Bepado');

        bepadoController.bepadoStore.getProxy().extraParams.articleId = me.subApplication.article.get('id');
        bepadoController.doReloadBepadoStore();
        bepadoController.enableTab();

    }

});
//{/block}


