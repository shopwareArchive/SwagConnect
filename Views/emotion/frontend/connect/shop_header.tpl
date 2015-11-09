<div class="table_row connect-dispatch-row">
	<div class="grid_6">
		<span class="title">
            {if empty($connectContent)}
                {s name=connect/checkout/dispatch_title_single}Versandkosten für die Lieferung{/s}
            {else}
                {s name=connect/checkout/dispatch_title}Versandkosten für die Lieferung {counter name=connectIndex} von {$connectShops|count + $addBaseShop}{/s}
            {/if}
		</span>
		&nbsp;
	</div>

	<div class="grid_3">
        {if isset($connectShippingCosts[$shopId])}
             {if $connectShippingCosts[$shopId] eq 0 }
                 <strong>
                     {se
                         namespace="frontend/plugins/index/delivery_informations"
                         name="DetailDataInfoShippingfree"}
                     {/se}
                 </strong>
             {/if}
        {/if}
	</div>

	<div class="grid_1">
		&nbsp;
	</div>

	<div class="grid_2 textright">
		{if !$hideSinglePrice}
			{if isset($connectShippingCosts[$shopId])}
                {if $connectShippingCosts[$shopId] > 0 }
				    {$connectShippingCosts[$shopId]|currency}
                {/if}
			{elseif $connectShippingCostsOrg}
				{$connectShippingCostsOrg|currency}
			{/if}
		{/if}
	</div>

	<div class="grid_2 textright">
		<strong>
        {if isset($connectShippingCosts[$shopId])}
            {if $connectShippingCosts[$shopId] > 0 }
                {$connectShippingCosts[$shopId]|currency}
            {/if}
		{elseif $connectShippingCostsOrg}
			{$connectShippingCostsOrg|currency}
		{/if}</strong>
	</div>
	<div class="clear"></div>
</div>