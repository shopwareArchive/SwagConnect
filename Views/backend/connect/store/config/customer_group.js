
Ext.define('Shopware.apps.Connect.store.config.CustomerGroup', {
    extend: 'Ext.data.Store',

    model : 'Shopware.apps.Base.model.CustomerGroup',
    pageSize: 1000,

    proxy:{
        extraParams: {
            showConnect: true
        },
        type:'ajax',
        url:'{url controller="ConnectConfig" action="getExportCustomerGroups"}',
        reader:{
            type:'json',
            root:'data',
            totalProperty:'total'
        }
    }
});
