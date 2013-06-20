<div class="table_row" style="border: none; min-height:20px;padding-bottom: 0;">
    <div class="grid_9 box">
        {*if $shopId}
        <span style="margin-left: 12px; background-color: #DD4800; color: #FFF; font-size: 10px; font-weight: bold; padding: 3px; text-transform: uppercase;">
            Separater Versand
        </span>
        {/if*}
        <h3 style="font-weight: bold; margin-left: 12px; display: inline-block;">
            {*Artikel von {if $shopId}{$bepadoShops[$shopId]->name}{else}{$sShopname}{/if}*}
            Lieferung {counter name=bepadoIndex} von {$bepadoShops|count + 1}
            {if $bepadoShippingCosts[$shopId]}
            - zzgl. {$bepadoShippingCosts[$shopId]|currency} Versandkosten
            {elseif $bepadoShippingCostsOrg}
            - zzgl. {$bepadoShippingCostsOrg|currency} Versandkosten
            {/if}
        </h3>
        <hr class="clear">
    </div>
</div>