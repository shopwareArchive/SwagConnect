//{block name="backend/connect/store/import/list"}
Ext.define('Shopware.apps.Connect.store.import.List', {
    extend: 'Ext.data.Store',

    groupField: 'category',

    model:'Shopware.apps.Connect.model.import.List',
    remoteSort: true,
    remoteFilter: true,
    pageSize: 20,
    proxy: {
        type: 'ajax',
        url: '{url action=getImportList}',
        reader: {
            type: 'json',
            root: 'data'
        }
    }
});
//{/block}
