//{block name="backend/plugin_manager/view/components/container"}
Ext.define('Shopware.apps.Connect.view.components.Container', {
    extend: 'Ext.container.Container',
    alternateClassName: 'Connect.container.Container',
    alias: 'widget.register-container-container',

    handler: null,

    initComponent: function() {
        var me = this;

        me.on('afterrender', function(comp) {

            comp.el.on('click', function() {
                if (me.disabled) {
                    return;
                }

                if (Ext.isFunction(me.handler)) {
                    me.handler();
                } else {
                    me.fireEvent('click', me);
                }
            });

        });
        me.callParent(arguments);
    }
});
//{/block}