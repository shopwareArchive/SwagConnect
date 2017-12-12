{extends file="parent:frontend/checkout/ajax_add_article.tpl"}

{block name='checkout_ajax_add_information'}
    {if !$sBasketInfo}
        {$smarty.block.parent}
    {/if}
{/block}