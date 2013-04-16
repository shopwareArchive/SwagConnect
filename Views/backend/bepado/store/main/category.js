//{block name="backend/bepado/store/main/category"}
Ext.define('Shopware.apps.Bepado.store.main.Category', {
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
        }
    }
});
//{/block}
