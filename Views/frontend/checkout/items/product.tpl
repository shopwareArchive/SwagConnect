{extends file="parent:frontend/checkout/items/product.tpl"}

{block name='frontend_checkout_cart_item_image_container_outer'}
    {if $shopId && $connectShopInfo}
        <div class="connect--connect-image-article">
			<span class="connect-image-article--text">
				Connect
			</span>
        </div>
    {/if}

    {$smarty.block.parent}
{/block}

{block name="frontend_checkout_cart_item_details_inline"}
    {$smarty.block.parent}
    {if $shopId}
        <div class="connect--additional-info">
            {if $showShippingCostsSeparately}
                <span class="connect--cart-label">{s name="frontend_checkout_cart_connect_dispatch"}Separater Versand{/s}</span>
            {/if}
            {if $connectShopInfo}
                <span class="connect--cart-reseller">Artikel von {$connectShops[$shopId]->name}</span>
            {/if}
        </div>
    {/if}
{/block}