//{block name="backend/article/store/connect"}
Ext.define('Shopware.apps.Article.store.Connect', {
    extend: 'Ext.data.Store',

    model:'Shopware.apps.Article.model.Connect',
    remoteSort: true,
    remoteFilter: true,
    pageSize: 20,
    proxy: {
        type: 'ajax',
        url: '{url controller=Connect action=getConnectData}',
        reader: {
            type: 'json',
            root: 'data'
        }
    }
});
//{/block}
