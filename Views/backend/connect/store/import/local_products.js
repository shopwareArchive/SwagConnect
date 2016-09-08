//{block name="backend/connect/store/import/local_products"}
Ext.define('Shopware.apps.Connect.store.import.LocalProducts', {
    extend : 'Ext.data.Store',

    autoLoad: false,
    pageSize: 10,
    fields: [
        { name: 'Article_id', type: 'integer' },
        { name: 'Detail_number',  type: 'string' },
        { name: 'Article_name',  type: 'string' },
        { name: 'Supplier_name',  type: 'string' },
        { name: 'Article_active',  type: 'boolean' },
        { name: 'Detail_purchasePrice',  type: 'float' },
        { name: 'Price_price',  type: 'float' },
        { name: 'Tax_name',  type: 'string' },
        { name: 'Attribute_connectMappedCategory',  type: 'int' }
    ],
    proxy : {
        type : 'ajax',
        api : {
            read : '{url controller=Import action=loadBothArticleTypes}'
        },
        reader : {
            type : 'json',
            root: 'data',
            totalProperty:'total'
        }
    }
});
//{/block}