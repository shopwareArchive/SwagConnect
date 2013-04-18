//{block name="backend/bepado/store/import/list"}
Ext.define('Shopware.apps.Bepado.store.import.List', {
    extend: 'Ext.data.Store',

    model:'Shopware.apps.Bepado.model.import.List',
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
