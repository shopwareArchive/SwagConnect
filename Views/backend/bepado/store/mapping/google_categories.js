//{block name="backend/connect/store/mapping/google_categories"}
Ext.define('Shopware.apps.Connect.store.mapping.GoogleCategories', {
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
