//{block name="backend/bepado/application"}
Ext.define('Shopware.apps.Bepado', {
    extend: 'Enlight.app.SubApplication',

    bulkLoad: true,
    loadPath: '{url action=load}',
    views: [
        'main.Window', 'main.Navigation', 'main.HomePage',
        'main.Panel', 'main.Config', 'main.Mapping',
        'export.Panel', 'import.Panel',
        'export.List', 'export.Filter',
        'import.List', 'import.Filter',
        'log.Panel', 'log.List', 'log.Filter', 'log.Tabs',
        'changed_products.Panel', 'changed_products.List', 'changed_products.Tabs', 'changed_products.Images',
        'prices.Panel', 'prices.List',
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