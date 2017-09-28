{extends file="parent:frontend/checkout/confirm_item.tpl"}

{block name="frontend_checkout_cart_item_details_inline"}
    {$smarty.block.parent}
    {if $shopId}
        {block name="frontend_checkout_connect_additional_info"}
            <div class="connect--additional-info">
                {block name="frontend_checkout_connect_additional_info_label"}
                    {if $showShippingCostsSeparately}
                        <span class="connect--cart-label">{s name="frontend_checkout_cart_connect_dispatch"}Separater Versand{/s}</span>
                    {/if}
                {/block}

                {block name="frontend_checkout_connect_additional_info_reseller"}
                    {if $connectShopInfo}
                        <span class="connect--cart-reseller">Artikel von {$connectShops[$shopId]->name}</span>
                    {/if}
                {/block}
            </div>
        {/block}
    {/if}
{/block}