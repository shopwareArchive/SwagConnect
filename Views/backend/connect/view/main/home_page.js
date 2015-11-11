//{namespace name=backend/connect/view/main}

// {include file="backend/connect/_resources/html/home_page.tpl" assign="homePageContent"}

//{block name='backend/connect/view/main/home_page'}
Ext.define('Shopware.apps.Connect.view.main.HomePage', {
    extend: 'Ext.panel.Panel',
    alias: 'widget.connect-home-page',

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
        me.htmlTpl = '{$homePageContent|replace:"\n":""|replace:"\r":""|replace:"connect":"' + marketplaceName + '"|replace:"marketplaceLogo":"' + marketplaceLogo + '"}';

        return me.htmlTpl;
    }
});
//{/block}