//{block name="backend/bepado/store/main/mapping"}
Ext.define('Shopware.apps.Bepado.store.main.Mapping', {
    extend : 'Ext.data.TreeStore',

    autoLoad: false,
    model : 'Shopware.apps.Base.model.Category',
    proxy : {
        type : 'ajax',
        api : {
            read : '{url controller=category action=getList}'
        },
        reader : {
            type : 'json',
            root: 'data'
        }
    }
});
//{/block}
