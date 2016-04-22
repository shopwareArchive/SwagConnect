//{namespace name=backend/connect/view/main}

/**
 * Shopware Controller - Cache backend module
 */
//{block name="backend/connect/controller/main"}
Ext.define('Shopware.apps.Connect.controller.Account', {

    extend: 'Enlight.app.Controller',

    /**
     * Class property which holds the main application if it is created
     *
     * @default null
     * @object
     */
    mainWindow: null,

    /**
     * Init component. Basically will create the app window and register to events
     */
    init: function () {
        var me = this;

        me.mainWindow = me.getView('main.Window').create({
            'action': me.subApplication.action
        }).show();

        me.callParent(arguments);
    }
});
//{/block}
