{foreach name=basket from=$bepadoContent item=bepadoItems key=shopId}

    {block name='frontend_checkout_cart_cart_head'}
        {include file="frontend/checkout/cart_header.tpl"}
    {/block}

    {foreach name=basket from=$bepadoItems item=sBasketItem key=key}
        {block name='frontend_checkout_cart_item'}
            {include file='frontend/checkout/cart_item.tpl'}
        {/block}
    {/foreach}
{/foreach}