//{block name="backend/connect/store/log/list"}
Ext.define('Shopware.apps.Connect.store.log.List', {
    extend: 'Ext.data.Store',

    model:'Shopware.apps.Connect.model.log.List',
    remoteSort: true,
    remoteFilter: true,
    pageSize: 20,
    proxy: {
        type: 'ajax',
        url: '{url action=getLogs}',
        reader: {
            type: 'json',
            root: 'data'
        }
    }
});
//{/block}
