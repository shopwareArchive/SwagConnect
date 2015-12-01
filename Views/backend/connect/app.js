//{block name="backend/connect/application"}
Ext.define('Shopware.apps.Connect', {
    extend: 'Enlight.app.SubApplication',

    bulkLoad: true,
    loadPath: '{url action=load}',
    views: [
        'main.Window', 'main.Navigation', 'main.Panel',
        'export.Panel', 'import.Panel',
        'export.List', 'export.Filter',
        'import.RemoteCategories', 'import.OwnCategories', 'import.RemoteProducts', 'import.LocalProducts',
        'log.Panel', 'log.List', 'log.Filter', 'log.Tabs',
        'changed_products.Panel', 'changed_products.List', 'changed_products.Tabs', 'changed_products.Images',
		'config.general.Panel', 'config.general.Form', 'config.import.Panel', 'config.export.Panel', 'config.Tabs',
        'config.import.Form', 'config.export.Form', 'config.marketplaceAttributes.Panel', 'config.marketplaceAttributes.Mapping',
        'config.units.Mapping'
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