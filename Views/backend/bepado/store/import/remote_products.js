//{block name="backend/bepado/store/import/remote_products"}
Ext.define('Shopware.apps.Bepado.store.import.RemoteProducts', {
    extend : 'Ext.data.Store',

    autoLoad: false,
    pageSize: 5,
    fields: [
        { name: 'id', type: 'integer' },
        { name: 'number',  type: 'string' },
        { name: 'name',  type: 'string' },
        { name: 'supplier',  type: 'string' },
        { name: 'price',  type: 'float' },
        { name: 'tax',  type: 'float' }
    ],
    proxy : {
        type : 'ajax',
        api : {
            read : '{url controller=Import action=loadArticlesByRemoteCategory}'
        },
        reader : {
            type : 'json',
            root: 'data',
            totalProperty:'total'
        }
    }
});
//{/block}