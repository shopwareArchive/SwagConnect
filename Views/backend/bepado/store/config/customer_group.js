
Ext.define('Shopware.apps.Bepado.store.config.CustomerGroup', {
    extend: 'Ext.data.Store',

    model : 'Shopware.apps.Base.model.CustomerGroup',
    pageSize: 1000,

    proxy:{
        extraParams: {
            showBepado: true
        },
        type:'ajax',
        url:'{url controller="BepadoConfig" action="getExportCustomerGroups"}',
        reader:{
            type:'json',
            root:'data',
            totalProperty:'total'
        }
    }
});
