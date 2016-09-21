//{namespace name=backend/connect/view/main}
//{block name="backend/index/view/menu" append}
Ext.define('Shopware.apps.Index.view.ConnectMenu', {
    override: 'Shopware.apps.Index.view.Menu',

    /**
     * @Override
     */
    initComponent: function() {
        var me = this, result;

            Shopware.app.Application.addSubApplication({
                    name: 'Shopware.apps.PluginManager',
                    params: {
                        hidden: false
                    }
                },
                false,
                function() {
                    var controller = Ext.create('Shopware.apps.PluginManager.controller.Plugin');
                    var record = Ext.create('Shopware.apps.PluginManager.model.Plugin', {
                        technicalName: 'SwagConnect'
                    });
                    record.reload(function(swagConnect) {
                        Shopware.Notification.createStickyGrowlMessage({
                            title: '{s name=plugin/update/header}New Connect plugin update available.{/s}',
                            text: '{s name=plugin/update/text}Please update your connect plugin.{/s}',
                            width: 400,
                            btnDetail: {
                                text: '{s name=plugin/update/btn}Update{/s}',
                                callback: function() {
                                    controller.init();
                                    Shopware.app.Application.fireEvent('update-plugin', swagConnect[0]);
                                }
                            }
                        });
                    });
                }
            );

        result = me.callParent(arguments);

        return result;
    }
});
//{/block}
