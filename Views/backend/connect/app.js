//{block name="backend/connect/application"}
Ext.define('Shopware.apps.Connect', {
    extend: 'Enlight.app.SubApplication',

    bulkLoad: true,
    loadPath: '{url action=load}',
    views: [
        'main.Window', 'main.TabPanel', 'main.Panel',
        'export.product.Panel', 'import.Panel',
        'export.product.List', 'export.product.Filter',
        'export.stream.Panel', 'export.stream.List',
        'import.RemoteCategories', 'import.OwnCategories', 'import.RemoteProducts', 'import.LocalProducts',
        'log.Panel', 'log.List', 'log.Filter', 'log.Tabs',
        'changed_products.Panel', 'changed_products.List', 'changed_products.Tabs', 'changed_products.Images',
		'config.general.Panel', 'config.general.Form', 'config.import.Panel', 'config.export.Panel',
        'config.import.Form', 'config.export.Form', 'config.import.UnitsMapping',
        'config.marketplaceAttributes.Panel', 'config.marketplaceAttributes.Mapping'
    ],
    controllers: [ 'Main', 'Import' ],

    //views: [],

    /**
     * This method will be called when all dependencies are solved and
     * all member controllers, models, views and stores are initialized.
     */
    launch: function() {
        var me = this;
        me.controller = me.getController('Main');
        return me.controller.mainWindow;
    }
});
//{/block}