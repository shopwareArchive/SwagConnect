{block name="backend/base/header/javascript" append}
    <script type="text/javascript">
        setTimeout(function(){
            Shopware.Notification.createStickyGrowlMessage({
                title: '{$falseVersionTitle|snippet:'falseVersionTitle':'backend/swagConnect'}',
                text: '{$falseVersionMessage|snippet:'falseVersionMessage':'backend/swagConnect'}',
                width: 400
            });
        }, 1000);
    </script>
{/block}