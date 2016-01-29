{block name="backend/base/header/javascript" append}
    <script type="text/javascript">
        setTimeout(function(){
            Shopware.Notification.createStickyGrowlMessage({
                title: '{$falseVersionTitle}',
                text: '{$falseVersionMessage}',
                width: 400
            });
        }, 1000);
    </script>
{/block}