//{namespace name=backend/bepado/view/main}

// {include file="backend/bepado/_resources/html/mapping_general.tpl" assign="mappingGeneralContent"}


//{block name="backend/bepado/view/mapping/general"}
Ext.define('Shopware.apps.Bepado.view.mapping.General', {
    extend: 'Ext.panel.Panel',
    alias: 'widget.bepado-mapping',

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
        me.htmlTpl = '{$mappingGeneralContent|replace:"\n":""|replace:"\r":""}';

        return me.htmlTpl;
    }
});
//{/block}