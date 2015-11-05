//{namespace name=backend/connect/view/main}
//{block name="backend/connect/view/changed_products/images"}

/**
 * A simple image overview component, which shows a given list of images
 * Fore the time being these images needs to be passed pipe separated
 */
Ext.define('Shopware.apps.Connect.view.changed_products.Images', {

    extend: 'Ext.form.FieldContainer',

    alias: 'widget.shopware-images-field',

    layout: {
        type: 'hbox',
        align: 'stretch'
    },

    mixins: [
        'Ext.form.field.Base'
    ],

    height: 120,

    // base image path
    mediaPath: '{link file=""}',

    // no media image url
    noMedia: '{link file="templates/_default/frontend/_resources/images/no_picture.jpg"}',

    // list of previews
    previews: [ ],


    initComponent: function() {
        var me = this;

        me.items = me.createItems();
        me.callParent(arguments);
    },

    /**
     * Create the items for this view
     *
     * @returns Array
     */
    createItems: function() {
        var me = this;

        return [
            me.createPreviewContainer()
        ];
    },


    /**
     * The preview container holds the image preview objects
     *
     * @returns Ext.container.Container
     */
    createPreviewContainer: function() {
        var me = this;

        me.previewContainer = Ext.create('Ext.container.Container', {
            overflowX: 'auto',
            flex: 1,
            style: "background: #fff",
            items: [ ]
        });
        return me.previewContainer;
    },


    /**
     * Creates the actual image previews from the value of this component
     *
     * @returns Array
     */
    createPreviews: function() {
        var me = this,
            images;

        if (!me.value) {
            return [ ];
        }
        images = me.value.split('|');


        Ext.each(images, function(image) {
            var prev = Ext.create('Ext.Img', {
                src: image.indexOf('http') == -1 ? me.mediaPath + image : image,
                height: 100,
                maxHeight: 100,
                padding: 5,
                margin: 5,
                style: "border-radius: 6px; border: 1px solid #c4c4c4;"
            });
            me.previews.push(prev);
            me.previewContainer.add(prev);
        });


        return me.previews;
    },


    /**
     * Update the previews. Removes previous previews first
     * @param image
     */
    updatePreviews: function(image) {
        var me = this;

        if (me.previewContainer) {
            Ext.each(me.previews, function(item) {
                me.previewContainer.remove(item);
            });
        }
        me.previews = [ ];
        me.createPreviews();
    },

    /**
     * Getter for the value
     *
     * @returns string
     */
    getValue: function() {
        return this.value;
    },

    /**
     * Setter for the value
     * @param value
     */
    setValue: function(value) {
        var me = this;

        this.value = value;
        this.updatePreviews(value);
    },

    /**
     * This function is used if an { @link Ext.data.Model } will be
     * updated with the form data.
     * The function has to return an object with the values which will
     * be updated in the model.
     *
     * @returns { Object }
     */
    getSubmitData: function() {
        var value = {};

        value[this.name] = this.value;
        return value;
    }
});
//{/block}