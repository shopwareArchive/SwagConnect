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

        if (readCookie('connectLogin') == 'true') {
            document.addEventListener("DOMContentLoaded", function(event) {
                document.body.innerHTML += '<a href="connect/autoLogin" id="connectButtonLogin" target="_blank"></a>';
                document.getElementById('connectButtonLogin').click();
                document.cookie = 'connectLogin=no';
            });
        }

        function readCookie(name) {
            var nameEQ = name + "=";
            var ca = document.cookie.split(';');
            for (var i = 0; i < ca.length; i++) {
                var c = ca[i];
                while (c.charAt(0) == ' ') c = c.substring(1, c.length);
                if (c.indexOf(nameEQ) == 0) {
                    return c.substring(nameEQ.length, c.length);
                }
            }
            return null;
        }
    </script>
{/block}