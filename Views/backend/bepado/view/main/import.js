//{namespace name=backend/bepado/view/main}

// {include file="backend/bepado/_resources/html/import.tpl" assign="importContent"}

//{block name='backend/bepado/view/main/import'}
Ext.define('Shopware.apps.Bepado.view.main.Import', {
    extend: 'Ext.panel.Panel',
    alias: 'widget.bepado-import-main',

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
        me.htmlTpl = '{$importContent|replace:"\n":""|replace:"\r":""}';

        return me.htmlTpl;
    }
});
//{/block}