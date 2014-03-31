//{namespace name=backend/bepado/view/main}

//{block name="backend/bepado/view/changed_products/tabs"}
Ext.define('Shopware.apps.Bepado.view.changed_products.Tabs', {
    extend: 'Ext.tab.Panel',
    alias: 'widget.bepado-changed-products-tabs',

    height: 300,

    border: false,

    title: '{s name=changed_products/changeview}Changes{/s}',


    initComponent: function() {
        var me = this;

        me.createItems();

        me.callParent(arguments);
    },

    /**
     * Creates the actual tabs for the known fields
     */
    createItems: function() {
        var me = this;

        me.fields = { };
        Ext.each(['price', 'shortDescription', 'longDescription', 'name', 'image', 'imageInitialImport'], function(field) {
            me.fields[field] = me.createContainer(field);
        });
    },

    /**
     * Creates the tab for given field type. Each tab will have a field for the remote and for the local value
     * so the user can compare them
     *
     * @param name
     * @returns Object
     */
    createContainer: function(name) {
        var me = this,
            config, applyButton,
            local, remote;

        // Define the fields for the given type
        switch (name) {
            case 'price':
                config = {
                    xtype: 'numberfield',
                    precision: 2,
                    title: me.getTranslatedTitle('price')
                };
                break;
            case 'name':
                config = {
                    xtype: 'textfield',
                    title: me.getTranslatedTitle('name')
                };
                break;
            case 'shortDescription':
                config = {
                    xtype: 'textarea',
                    title: me.getTranslatedTitle('shortDescription'),
                    height: 150
                };
                break;
            case 'longDescription':
                config = {
                    xtype: 'textarea',
                    title: me.getTranslatedTitle('longDescription'),
                    height: 150
                };
                break;
            case 'image':
                config = {
                    xtype: 'shopware-images-field',
                    title: me.getTranslatedTitle('image'),
                    margin: 10
                };
                break;
            case 'imageInitialImport':
                config = {
                    xtype: 'shopware-images-field',
                    title: me.getTranslatedTitle('image'),
                    margin: 10
                };
                break;
        }

        // Define some default options for the local / remote field
        // The name is generated from the passed name and the prefix "local"/"remote"
        local = {
            fieldLabel: '{s name=changed_products/local}Local{/s}',
            name: name + 'Local',
            enabled: false
        };
        remote = {
            fieldLabel: '{s name=changed_products/remote}Remote{/s}',
            name: name + 'Remote',
            enabled: false
        };


        // Merge local/remote objects and the generated type-related object
        Ext.apply(local, config);
        Ext.apply(remote, config);

        applyButton = Ext.create('Ext.button.Button', {
            text: '{s name=changed_products/applyButton}Apply changes now{/s}'
        });

        // Create a form and put the local and remote field into
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
                applyButton: applyButton,
                items: [applyButton, local, remote]
            });
    }
});
//{/block}