//{block name="backend/bepado/store/main/navigation"}
Ext.define('Shopware.apps.Bepado.store.main.Navigation', {
    extend: 'Ext.data.TreeStore',

    root: {
        expanded: true,
        children: [
            { id: 'config', text: "Konfiguration", leaf: true },
            { id: 'mapping', text: "Mapping", leaf: true },
            { id: 'export', text: "Produkt-Export", leaf: true },
            { id: 'import', text: "Produkt-Import", leaf: true }
        ]
    }
});
//{/block}
