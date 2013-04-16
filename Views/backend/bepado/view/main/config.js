//{namespace name=backend/bepado/view/main}

//{block name="backend/bepado/view/main/config"}
Ext.define('Shopware.apps.Bepado.view.main.Config', {
    extend: 'Shopware.form.ConfigPanel',
    alias: 'widget.bepado-config',

    cls: 'shopware-form',
    layout: 'anchor',
    autoScroll: true,
    bodyPadding: 10,
    injectActionButtons: true,

    initComponent: function() {
        var me = this;

        if(!me.shopStore.getCount()) {
            me.shopStore.load();
        }

        me.formStore.on('load', me.onLoadForm, me, { single: true });
        me.formStore.load({
            filters: [{
                property: 'name',
                value: 'SwagBepado'
            }]
        });

        Ext.form.Panel.superclass.initComponent.apply(this, arguments);
    }
});
//{/block}