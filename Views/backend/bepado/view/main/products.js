//{namespace name=backend/bepado/view/main}

// {include file="backend/bepado/_resources/html/products.tpl" assign="productsContent"}

//{block name='backend/bepado/view/main/products'}
Ext.define('Shopware.apps.Bepado.view.main.Products', {
    extend: 'Ext.panel.Panel',
    alias: 'widget.bepado-products-general',

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
        me.htmlTpl = '{$productsContent|replace:"\n":""|replace:"\r":""}';

        return me.htmlTpl;
    }
});
//{/block}