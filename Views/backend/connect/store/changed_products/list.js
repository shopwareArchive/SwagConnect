//{block name="backend/connect/store/changed_products/list"}
Ext.define('Shopware.apps.Connect.store.changed_products.List', {
    extend: 'Ext.data.Store',

    model:'Shopware.apps.Connect.model.changed_products.List',
    remoteSort: true,
    remoteFilter: true,
    pageSize: 20,
    proxy: {
        type: 'ajax',
        url: '{url action=getChangedProducts}',
        reader: {
            type: 'json',
            root: 'data'
        }
    }
});
//{/block}
