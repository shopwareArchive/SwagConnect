//{block name="backend/connect/store/mapping/connect_categories_import"}
Ext.define('Shopware.apps.Connect.store.mapping.ConnectCategoriesImport', {
    extend : 'Ext.data.TreeStore',

    autoLoad: false,
    fields: [
        { name: 'id', type: 'string' },
        { name: 'name',  type: 'string' }
    ],
    proxy : {
        type : 'ajax',
        api : {
            read : '{url action=getCategoryList}'
        },
        reader : {
            type : 'json',
            root: 'data'
        },
        extraParams:  {
            type: 'import'
        }
    }
});
//{/block}
