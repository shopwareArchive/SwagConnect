//{block name="backend/bepado/application"}
Ext.define('Shopware.apps.Bepado', {
    extend: 'Enlight.app.SubApplication',

    bulkLoad: true,
    loadPath: '{url action=load}',
    views: [
        'main.Window', 'main.Navigation', 'main.HomePage',
        'main.Panel',
        'export.Panel', 'import.Panel',
        'export.List', 'export.Filter',
        'import.List', 'import.Filter',
        'main.Products',
        'log.Panel', 'log.List', 'log.Filter', 'log.Tabs',
        'changed_products.Panel', 'changed_products.List', 'changed_products.Tabs', 'changed_products.Images',
        'mapping.General', 'mapping.Export', 'mapping.Import',
		'config.general.Panel', 'config.general.Form', 'config.general.Description', 'config.import.Panel', 'config.export.Panel', 'config.Tabs',
        'config.import.Description', 'config.import.Form', 'config.export.Description', 'config.export.Form',
        'config.units.Panel', 'config.units.Mapping', 'config.shippingGroups.Panel', 'config.shippingGroups.List'
    ],
    controllers: [ 'Main' ],

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