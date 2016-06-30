{block name="backend/base/header/css" append}
<link rel="stylesheet" href="{link file='backend/connect/_resources/styles/connect-styles.css'}" />
<link rel="stylesheet" href="{link file='backend/connect/_resources/styles/sw-connect-style.css'}" />
<style type="text/css">
    .connect-icon {
        background:url({$marketplaceIcon}) no-repeat 0 0 !important;
    }
    .connect-icon-green {
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
        var defaultMarketplace = '{$defaultMarketplace}';
        var isFixedPriceAllowed = '{$isFixedPriceAllowed}';
        var purchasePriceInDetail = '{$purchasePriceInDetail}';
    </script>
{/block}