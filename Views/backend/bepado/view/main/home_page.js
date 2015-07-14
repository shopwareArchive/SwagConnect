//{namespace name=backend/bepado/view/main}

// {include file="backend/bepado/_resources/html/home_page.tpl" assign="homePageContent"}

//{block name='backend/bepado/view/main/home_page'}
Ext.define('Shopware.apps.Bepado.view.main.HomePage', {
    extend: 'Ext.panel.Panel',
    alias: 'widget.bepado-home-page',

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
        me.htmlTpl = '{$homePageContent|replace:"\n":""|replace:"\r":""|replace:"bepado":"' + marketplaceName + '"}';

        return me.htmlTpl;
    }
});
//{/block}