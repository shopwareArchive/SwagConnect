//{namespace name="backend/bepado/view/main"}
//{block name="backend/article/view/bepado_form"}
Ext.define('Shopware.apps.Article.view.BepadoForm', {
    extend: 'Ext.form.Panel',

    alias: 'widget.article-bepado-form',

    defaults: {
        margin: 10,
        labelWidth: 155,
        anchor: '100%'
    },


    initComponent: function() {
        var me = this;

        me.items = me.getBepadoContent();

        me.callParent();
    },

    getBepadoContent: function() {
        var me = this;

        return [
            me.getFixedPriceFieldSet(),
            me.getBepadoImportConfigFieldSet(),
            me.getShippingCostsFieldset()
        ];

    },

    getFixedPriceFieldSet: function() {
        var me = this;

        return {
            xtype: 'fieldset',
            defaults: me.defaults,
            title: '{s name=fixedPriceConfig}Fixed price configuration{/s}',
            items:
                [
                    {
                        xtype: 'label',
                        html: '{s name=fixedPriceWarning}<strong>Warning:</strong> Fixed prices may only be used for products which are subject to price fixing by law.{/s}'
                    },
                    me.getFixedPriceCombo()
                ]
        };
    },


    getBepadoImportConfigFieldSet: function() {
        var me = this;

        me.bepadoLeftContainer = Ext.create('Ext.container.Container', {
            columnWidth:0.5,
            defaults: {
                labelWidth: 155,
                anchor: '100%'
            },
            padding: '0 20 0 0',
            layout: 'anchor',
            border:false,
            items:me.getLeftContainer()
        });

        me.bepadoRightContainer = Ext.create('Ext.container.Container', {
            columnWidth:0.5,
            layout: 'anchor',
            defaults: {
                labelWidth: 155,
                anchor: '100%'
            },
            border:false,
            items:me.getRightContainer()
        });

        return {
            xtype: 'fieldset',
            layout: 'column',
            defaults: me.defaults,
            title: '{s name=overrideConfig}Field update configuration{/s}',
            items:
                [
                    me.bepadoLeftContainer,
                    me.bepadoRightContainer
                ]
        };
    },

    /**
     * Creates shipping group fieldset
     * in bepado article tab
     */
    getShippingCostsFieldset: function() {
        var me = this;

        return {
            xtype: 'fieldset',
            defaults: me.defaults,
            title: '{s name=shippingCosts}Shipping costs{/s}',
            items:
                [ me.getShippingGroupCombo() ]
        };
    },

    getLeftContainer: function() {
        var me = this;

        return [
            me.getOverwriteCombo('{s name=updatePrice}Update prices{/s}', 'updatePrice'),
            me.getOverwriteCombo('{s name=updateImage}Update images{/s}', 'updateImage'),
            me.getOverwriteCombo('{s name=updateName}Update name{/s}', 'updateName')
        ];
    },

    getRightContainer: function() {
        var me = this;

        return [
            me.getOverwriteCombo('{s name=updateLongDescription}Update long description{/s}', 'updateLongDescription'),
            me.getOverwriteCombo('{s name=updateShortDescription}Update short description{/s}', 'updateShortDescription')
        ];
    },

    getOverwriteCombo: function(text, dataField) {
        var me = this;

        return {
            xtype: 'combobox',
            store: me.getOverwriteStore(),
            displayField: 'description',
            valueField: 'value',
            fieldLabel: text,
            name: dataField,
            editable: false,
            emptyText: '{s name=automatically}Automatic{/s}'
        };
    },

    getOverwriteStore: function() {
        var me = this;

        return Ext.create('Ext.data.Store', {
            fields: [ { name: 'value', useNull: true }, { name: 'description' } ],
            data: [
                { value: 'overwrite', description: '{s name=automatically}Automatic{/s}' },
                { value: 'no-overwrite', description: '{s name=manually}Manual{/s}' }
            ]
        });

    },

    getFixedPriceCombo: function() {
        var me = this;

        return me.bepadoFixedPrice = Ext.create('Ext.form.field.Checkbox', {
            labelWidth: 155,
            name: 'fixedPrice',
            fieldLabel: '{s name=bepadoFixedPrice}Enable price fixing{/s}',
            inputValue: true,
            uncheckedValue:false
        })
    },

    /**
     * Creates shipping group combo
     */
    getShippingGroupCombo: function() {
        var me = this;

        return me.bepadoShippingGroup = Ext.create('Ext.form.ComboBox', {
            labelWidth: 155,
            name: 'shippingGroupName',
            fieldLabel: '{s name=config/shipping_groups/shipping_group}Shipping group{/s}',
            store: me.getShippingGroupStore(),
            displayField: 'groupName',
            valueField: 'groupName',
            emptyText: Ext.String.format('{s name=config/shipping_groups/shipping_group_empty_text}[0] default{/s}', marketplaceName)
        })
    },

    /**
     * Returns instance of shipping groups store
     */
    getShippingGroupStore: function() {
        var me = this;
        if (!me.shippingGroupStore) {
            me.shippingGroupStore = Ext.create('Shopware.apps.Bepado.store.shippingGroup.Groups').load();
        }

        return me.shippingGroupStore;
    }
});
//{/block}