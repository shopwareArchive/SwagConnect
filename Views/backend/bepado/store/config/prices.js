//{block name="backend/bepado/store/config/prices"}
Ext.define('Shopware.apps.Bepado.store.config.Prices', {
    extend: 'Ext.data.Store',

    model:'Shopware.apps.Bepado.model.config.Prices',
    remoteSort: true,
    remoteFilter: true,
    pageSize: 20,
    proxy: {
        type: 'ajax',
        url: '{url action=getPriceConfig}',
        reader: {
            type: 'json',
            root: 'data'
        }
    }
});
//{/block}
