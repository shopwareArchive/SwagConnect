{extends file="parent:frontend/checkout/confirm_item.tpl"}

{block name="frontend_checkout_cart_item_details_inline"}
    {$smarty.block.parent}
    {if $shopId}
        {block name="frontend_checkout_bepado_additional_info"}
            <div class="bepado--additional-info">
                {block name="frontend_checkout_bepado_additional_info_label"}
                    {if $showShippingCostsSeparately}
                        <span class="bepado--cart-label">{s name="frontend_checkout_cart_bepado_dispatch"}Separater Versand{/s}</span>
                    {/if}
                {/block}

                {block name="frontend_checkout_bepado_additional_info_reseller"}
                    {if $bepadoShopInfo}
                        <span class="bepado--cart-reseller">Artikel von {$bepadoShops[$shopId]->name}</span>
                    {/if}
                {/block}
            </div>
        {/block}
    {/if}
{/block}