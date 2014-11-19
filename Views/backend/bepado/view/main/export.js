//{namespace name=backend/bepado/view/main}

// {include file="backend/bepado/_resources/html/export.tpl" assign="exportContent"}

//{block name='backend/bepado/view/main/export'}
Ext.define('Shopware.apps.Bepado.view.main.Export', {
    extend: 'Ext.panel.Panel',
    alias: 'widget.bepado-export-main',

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
        me.htmlTpl = '{$exportContent|replace:"\n":""|replace:"\r":""}';

        return me.htmlTpl;
    }
});
//{/block}