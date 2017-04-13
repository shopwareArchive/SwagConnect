//{namespace name=backend/connect/view/main}
//{block name="backend/connect/view/export/stream/custom_checkbox_model"}
Ext.define('Shopware.apps.Connect.view.export.stream.CustomCheckboxModel', {
    extend: 'Ext.selection.CheckboxModel',
    onHeaderClick: function (headerCt, header, e) {
        if (header.isCheckerHd) {
            e.stopEvent();
            var me = this, isChecked = header.el.hasCls(Ext.baseCSSPrefix + 'grid-hd-checker-on');
            me.preventFocus = true;
            if (isChecked) {
                // Pass true as a parameter to prevent selectionchanged and select events firing
                me.deselectAll();
                me.fireEvent('deselectall', me);
            }
            else {
                // Pass true as a parameter to prevent selectionchanged and select events firing
                me.selectAll();
                me.fireEvent('selectall', me);
            }
            delete me.preventFocus;
        }
    }
});
//{/block}
