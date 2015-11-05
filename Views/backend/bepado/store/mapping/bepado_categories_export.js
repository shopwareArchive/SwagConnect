//{block name="backend/connect/store/mapping/connect_categories_export"}
Ext.define('Shopware.apps.Connect.store.mapping.ConnectCategoriesExport', {
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
            type: 'export'
        }
    }
});
//{/block}
