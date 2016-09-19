//{namespace name=backend/connect/view/main}
//{block name="backend/index/view/menu" append}
Ext.define('Shopware.apps.Index.view.ConnectMenu', {
    override: 'Shopware.apps.Index.view.Menu',

    /**
     * @Override
     */
    initComponent: function() {
        var me = this, result;

        setTimeout(function(){
            Shopware.Notification.createStickyGrowlMessage({
                title: '{$falseVersionTitle}',
                text: '{$falseVersionMessage}',
                width: 400
            });
        }, 1000);

        result = me.callParent(arguments);

        return result;
    }
});
//{/block}
