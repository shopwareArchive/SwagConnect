//{block name="backend/connect/store/export/stream_list"}
Ext.define('Shopware.apps.ProductStream.store.ConnectSupplierList', {
    extend: 'Ext.data.Store',

    model:'Shopware.apps.ProductStream.model.ConnectSupplierList',
    autoLoad: false,
    batch: true,
    remoteSort: true,
    remoteFilter: true,
    pageSize: 20,
    proxy: {
        type: 'ajax',
        url: '{url controller="import" action="getSuppliers"}',
        reader: {
            type: 'json',
            root: 'data'
        }
    }
});
//{/block}