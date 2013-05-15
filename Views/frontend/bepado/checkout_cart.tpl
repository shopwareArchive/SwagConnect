{foreach name=basket from=$bepadoContent item=bepadoItems key=shopId}

    {include file='frontend/bepado/shop_header.tpl'}

    {include file="frontend/checkout/cart_header.tpl"}

    {foreach name=basket from=$bepadoItems item=sBasketItem key=key}
        {block name='frontend_checkout_cart_item'}
            {include file='frontend/checkout/cart_item.tpl'}
        {/block}
    {/foreach}
{/foreach}