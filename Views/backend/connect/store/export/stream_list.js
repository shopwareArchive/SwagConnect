//{block name="backend/connect/store/export/stream_list"}
Ext.define('Shopware.apps.Connect.store.export.StreamList', {
    extend: 'Ext.data.Store',

    model:'Shopware.apps.Connect.model.export.StreamList',
    remoteSort: true,
    remoteFilter: true,
    pageSize: 20,
    autoLoad: true,
    groupField: 'type',
    proxy: {
        type: 'ajax',
        url: '{url action=getStreamList}',
        reader: {
            type: 'json',
            root: 'data'
        }
    }
});
//{/block}
