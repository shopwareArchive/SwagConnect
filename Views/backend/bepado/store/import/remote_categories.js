//{block name="backend/bepado/store/import/remote_categories"}
Ext.define('Shopware.apps.Bepado.store.import.RemoteCategories', {
    extend : 'Ext.data.TreeStore',

    autoLoad: false,
    fields: [
        { name: 'id', type: 'string' },
        { name: 'text',  type: 'string', mapping: 'name' }
    ],
    proxy : {
        type : 'ajax',
        api : {
            read : '{url controller=Import action=getImportedProductCategoriesTree}'
        },
        reader : {
            type : 'json',
            root: 'data'
        }
    }
});
//{/block}