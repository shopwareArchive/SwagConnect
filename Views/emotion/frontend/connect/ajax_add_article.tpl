-{block name='frontend_checkout_ajax_add_article_action_buttons' prepend}
    {if $connectProduct || $hasConnectProduct}
        <script src="{link file='frontend/_resources/javascripts/connect.js'}"></script>
    {/if}
{/block}
