//{block name="backend/connect/store/import/remote_categories"}
Ext.define('Shopware.apps.Connect.store.import.RemoteCategories', {
    extend : 'Ext.data.TreeStore',

    autoLoad: false,
    fields: [
        { name: 'id', type: 'string' },
        { name: 'categoryId', type: 'string' },
        { name: 'text',  type: 'string', mapping: 'name' },
        { name: 'expanded', type: 'boolean', defaultValue: false, persist: false, mapping: 'expanded' }
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