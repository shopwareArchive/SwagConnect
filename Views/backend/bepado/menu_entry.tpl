{block name="backend/base/header/css" append}
<link rel="stylesheet" href="{link file='backend/bepado/_resources/styles/bepado-styles.css'}" />
<style type="text/css">
    .bepado-icon {
        background:url({$marketplaceIcon}) no-repeat 0 0 !important;
    }
    .bepado-icon-green {
        background:url({$marketplaceIncomingIcon}) no-repeat 0 0 !important;
    }
    {*.bp-home-page .logo {*}
        {*background: url({$marketplaceLogo}) no-repeat 78% 20% !important;*}
    {*}*}
</style>
{/block}

{block name="backend/base/header/javascript" append}
    <script type="text/javascript">
        var marketplaceName = '{$marketplaceName}';
        var marketplaceNetworkUrl = '{$marketplaceNetworkUrl}';
        var marketplaceLogo = '{$marketplaceLogo}';
    </script>
{/block}