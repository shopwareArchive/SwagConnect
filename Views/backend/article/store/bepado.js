//{block name="backend/article/store/bepado"}
Ext.define('Shopware.apps.Article.store.Bepado', {
    extend: 'Ext.data.Store',

    model:'Shopware.apps.Article.model.Bepado',
    remoteSort: true,
    remoteFilter: true,
    pageSize: 20,
    proxy: {
        type: 'ajax',
        url: '{url controller=Bepado action=getBepadoData}',
        reader: {
            type: 'json',
            root: 'data'
        }
    }
});
//{/block}
