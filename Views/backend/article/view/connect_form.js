//{namespace name="backend/connect/view/main"}
//{block name="backend/article/view/connect_form"}
Ext.define('Shopware.apps.Article.view.ConnectForm', {
    extend: 'Ext.form.Panel',

    alias: 'widget.article-connect-form',

    defaults: {
        margin: 10,
        labelWidth: 155,
        anchor: '100%'
    },


    initComponent: function () {
        var me = this;

        me.items = me.getConnectContent();

        me.callParent();
    },

    getConnectContent: function () {
        var me = this;

        return [
            me.getFixedPriceFieldSet(),
            me.getConnectImportConfigFieldSet()
        ];
    },

    getFixedPriceFieldSet: function () {
        var me = this;

        me.connectFixedPriceFieldset = Ext.create('Ext.form.FieldSet', {
            defaults: me.defaults,
            title: '{s name=connect/fixedPriceConfig}Fixed price configuration{/s}',
            items: [
                {
                    xtype: 'label',
                    html: '{s name=connect/fixedPriceWarning}<strong>Warning:</strong> Fixed prices may only be used for products which are subject to price fixing by law.{/s}'
                },
                me.getFixedPriceCombo()
            ]
        });

        return me.connectFixedPriceFieldset;
    },


    getConnectImportConfigFieldSet: function () {
        var me = this;

        me.connectLeftContainer = Ext.create('Ext.container.Container', {
            columnWidth: 0.5,
            defaults: {
                labelWidth: 155,
                anchor: '100%'
            },
            padding: '0 20 0 0',
            layout: 'anchor',
            border: false,
            items: me.getLeftContainer()
        });

        me.connectRightContainer = Ext.create('Ext.container.Container', {
            columnWidth: 0.5,
            layout: 'anchor',
            defaults: {
                labelWidth: 155,
                anchor: '100%'
            },
            border: false,
            items: me.getRightContainer()
        });

        return {
            xtype: 'fieldset',
            layout: 'column',
            defaults: me.defaults,
            title: '{s name=connect/overrideConfig}Field update configuration{/s}',
            items: [
                me.connectLeftContainer,
                me.connectRightContainer
            ]
        };
    },

    getLeftContainer: function () {
        var me = this;

        return [
            me.getOverwriteCombo('{s name=connect/updatePrice}Update prices{/s}', 'updatePrice'),
            me.getOverwriteCombo('{s name=connect/updateImage}Update images{/s}', 'updateImage'),
            me.getOverwriteCombo('{s name=connect/updateName}Update name{/s}', 'updateName')
        ];
    },

    getRightContainer: function () {
        var me = this;

        return [
            me.getOverwriteCombo('{s name=connect/updateLongDescription}Update long description{/s}', 'updateLongDescription'),
            me.getOverwriteCombo('{s name=connect/updateShortDescription}Update short description{/s}', 'updateShortDescription')
        ];
    },

    getOverwriteCombo: function (text, dataField) {
        var me = this;

        return {
            xtype: 'combobox',
            store: me.getOverwriteStore(),
            displayField: 'description',
            valueField: 'value',
            fieldLabel: text,
            name: dataField,
            editable: false,
            emptyText: '{s name=connect/automatically}Automatic{/s}'
        };
    },

    getOverwriteStore: function () {
        return Ext.create('Ext.data.Store', {
            fields: [{
                name: 'value',
                useNull: true
            }, {
                name: 'description'
            }],
            data: [{
                value: 'overwrite',
                description: '{s name=connect/automatically}Automatic{/s}'
            }, {
                value: 'no-overwrite',
                description: '{s name=connect/manually}Manual{/s}'
            }]
        });
    },

    getFixedPriceCombo: function () {
        var me = this;

        return me.connectFixedPrice = Ext.create('Ext.form.field.Checkbox', {
            labelWidth: 155,
            name: 'fixedPrice',
            fieldLabel: '{s name=connect/connectFixedPrice}Enable price fixing{/s}',
            inputValue: true,
            uncheckedValue: false
        })
    }
});
//{/block}