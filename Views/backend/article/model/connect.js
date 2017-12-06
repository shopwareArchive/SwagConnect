//{block name="backend/article/model/connect"}
Ext.define('Shopware.apps.Article.model.Connect', {
    extend: 'Ext.data.Model',

    fields: [
        //{block name="backend/article/model/connect/fields"}{/block}
        { name: 'fixedPrice', type: 'boolean' },
        { name: 'shopId', type: 'int', useNull: true },
        { name: 'sourceId', type: 'string', useNull: true },
        { name: 'updatePrice', type: 'string', useNull: true  },
        { name: 'updateImage', type: 'string', useNull: true  },
        { name: 'updateMainImage', type: 'string', useNull: true  },
        { name: 'updateLongDescription', type: 'string', useNull: true  },
        { name: 'updateShortDescription', type: 'string', useNull: true  },
        { name: 'updateName', type: 'string', useNull: true  }
    ],


    /**
     * Configure the data communication
     * @object
     */
    proxy:{
        /**
         * Set proxy type to ajax
         * @string
         */
        type:'ajax',

        /**
         * Configure the url mapping for the different
         * store operations based on
         * @object
         */
        api: {
            create: '{url controller=Connect action="saveConnectAttribute"}',
            update: '{url controller=Connect action="saveConnectAttribute"}'
        },

        /**
         * Configure the data reader
         * @object
         */
        reader:{
            type:'json',
            root:'data',
            totalProperty:'total'
        }
    }
});
//{/block}