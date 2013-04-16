//{block name="backend/bepado/store/export/list"}
Ext.define('Shopware.apps.Bepado.store.export.List', {
    extend: 'Ext.data.Store',

    model:'Shopware.apps.Bepado.model.export.List',
    remoteSort: true,
    remoteFilter: true,
    pageSize: 20,
    proxy: {
        type: 'ajax',
        url: '{url action=getExportList}',
        reader: {
            type: 'json',
            root: 'data'
        }
    }
});
//{/block}
