//{namespace name=backend/bepado/view/main}

//{block name="backend/bepado/view/changed_products/accordion"}
Ext.define('Shopware.apps.Bepado.view.changed_products.Accordion', {
    extend: 'Ext.tab.Panel',
    alias: 'widget.bepado-changed-products-accordion',

    height: 300,

    border: false,

    title: '{s name=changed_products/changeview}Changes{/s}',


    initComponent: function() {
        var me = this;

        me.createItems();

        me.callParent(arguments);
    },

    createItems: function() {
        var me = this;

        me.fields = { };
        Ext.each(['price', 'shortDescription', 'longDescription', 'name', 'image'], function(field) {
            me.fields[field] = me.createContainer(field);
        });
    },

    createContainer: function(name) {
        var me = this,
            config,
            local, remote;

        switch (name) {
            case 'price':
                config = {
                    xtype: 'numberfield',
                    precision: 2,
                    title: 'price'
                };
                break;
            case 'name':
                config = {
                    xtype: 'textfield',
                    title: 'name'
                };
                break;
            case 'shortDescription':
                config = {
                    xtype: 'textarea',
                    title: 'shortDescription',
                    height: 150
                };
                break;
            case 'longDescription':
                config = {
                    xtype: 'textarea',
                    title: 'longDescription',
                    height: 150
                };
                break;
            case 'image':
                config = {
                    xtype: 'shopware-images-field',
                    title: 'image'
                };
        }

        local = {
            fieldLabel: 'Current',
            name: name + 'Local',
            enabled: false
        };
        remote = {
            fieldLabel: 'Remote',
            name: name + 'Remote',
            enabled: false
        };


        Ext.apply(local, config);
        Ext.apply(remote, config);

        return Ext.create('Ext.form.Panel', {
                autoScroll: true,
                title: config.title,
                border: false,
                layout: {
                    type: 'vbox',
                    align: 'stretch'
                },
                defaults: {
                    padding: 10,
                    border: false
                },
                items: [local, remote]
            });
    }

});
//{/block}