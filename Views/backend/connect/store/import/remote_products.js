//{block name="backend/connect/store/import/remote_products"}
Ext.define('Shopware.apps.Connect.store.import.RemoteProducts', {
    extend : 'Ext.data.Store',

    autoLoad: false,
    pageSize: 10,
    fields: [
        { name: 'Article_id', type: 'integer' },
        { name: 'Detail_number',  type: 'string' },
        { name: 'Article_name',  type: 'string' },
        { name: 'Supplier_name',  type: 'string' },
        { name: 'Article_active',  type: 'boolean' },
        { name: 'Price_basePrice',  type: 'float' },
        { name: 'Price_price',  type: 'float' },
        { name: 'Tax_name',  type: 'string' }
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