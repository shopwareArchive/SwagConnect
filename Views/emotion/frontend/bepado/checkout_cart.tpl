{foreach name=basket from=$connectContent item=connectItems key=shopId}

    {block name='frontend_checkout_cart_cart_head'}
        {include file="frontend/checkout/cart_header.tpl"}
    {/block}

    {foreach name=connectItems from=$connectItems item=sBasketItem key=key}
        {block name='frontend_checkout_cart_item'}
            {include file='frontend/checkout/cart_item.tpl'}
        {/block}
    {/foreach}

    {if !$smarty.foreach.basket.last}
        <div class="border-top">
        </div>
    {/if}
{/foreach}