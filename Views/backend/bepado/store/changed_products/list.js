//{block name="backend/bepado/store/changed_products/list"}
Ext.define('Shopware.apps.Bepado.store.changed_products.List', {
    extend: 'Ext.data.Store',

    model:'Shopware.apps.Bepado.model.changed_products.List',
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
