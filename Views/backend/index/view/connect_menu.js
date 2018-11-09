//{namespace name=backend/connect/view/main}
//{block name="backend/index/view/menu" append}
Ext.define('Shopware.apps.Index.view.ConnectMenu', {
    override: 'Shopware.apps.Index.view.Menu',

    /**
     * @Override
     */
    initComponent: function() {
        var me = this, result;

        me.isUpdateAvailable();

        result = me.callParent(arguments);

        return result;
    },

    isUpdateAvailable: function() {
        var me = this;

        Ext.Ajax.request({
            url: '{url controller=Connect action=checkPluginVersion}',
            async: true,
            success: function (response) {
                if (!response || !response.responseText) {
                    return;
                }

                var result = Ext.decode(response.responseText);
                if (!result.success) {
                    return;
                }

                if (result.updateAvailable) {
                    me.createUpdateMessage();
                }
            }
        });
    },

    createUpdateMessage: function() {
        Shopware.app.Application.addSubApplication({
                name: 'Shopware.apps.PluginManager'
            },
            false,
            function () {
                var controller = Ext.create('Shopware.apps.PluginManager.controller.Plugin');
                var store = Ext.create('Shopware.apps.PluginManager.store.LocalPlugin');
                var record = Ext.create('Shopware.apps.PluginManager.model.Plugin', {
                    technicalName: 'SwagConnect'
                });
                record.reload(function (swagConnect) {
                    Shopware.Notification.createStickyGrowlMessage({
                        title: '{s name=plugin/update/header}New Connect plugin update available.{/s}',
                        text: '{s name=plugin/update/text}Please update your connect plugin.{/s}',
                        width: 400,
                        btnDetail: {
                            text: '{s name=plugin/update/btn}Update{/s}',
                            callback: function () {
                                var plugin = swagConnect[0];
                                controller.init();
                                controller.authenticateForUpdate(plugin, function () {
                                    controller.startPluginDownload(plugin, function () {
                                        controller.displayLoadingMask(plugin, '{s name=execute_update}Plugin is being updated{/s}', false);
                                        store.load({
                                            scope: this,
                                            callback: function () {
                                                controller.executePluginUpdate(plugin, function () {
                                                });
                                            }
                                        });
                                    });
                                });
                            }
                        }
                    });
                });
            }
        );
    }
});
//{/block}
