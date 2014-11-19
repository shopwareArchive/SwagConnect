//{namespace name=backend/bepado/view/main}

// {include file="backend/bepado/_resources/html/settings_main.tpl" assign="settingsContent"}

//{block name='backend/bepado/view/main/settings_main'}
Ext.define('Shopware.apps.Bepado.view.main.SettingsMain', {
    extend: 'Ext.panel.Panel',
    alias: 'widget.bepado-settings-main',

    //border: false,
    layout: 'fit',
    bodyPadding: 25,
    autoScroll: true,
    unstyled: true,

    initComponent: function() {
        var me = this;

        me.html = me.getHTMLContent();
        me.callParent(arguments);
    },

    getHTMLContent: function() {
        var me = this;
        me.htmlTpl = '{$settingsContent|replace:"\n":""|replace:"\r":""}';

        return me.htmlTpl;
    }
});
//{/block}