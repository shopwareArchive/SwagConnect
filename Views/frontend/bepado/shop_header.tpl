<div class="table_row" style="border: none; min-height:20px">
    <div class="grid_9 box">
        {if $shopId}
        <span style="margin-left: 12px; background-color: #DD4800; color: #FFF; font-size: 10px; font-weight: bold; padding: 3px; text-transform: uppercase;">
            Separater Versand
        </span>
        {/if}
        <h3 style="font-weight: bold; margin-left: 12px; display: inline-block;">
            Artikel von {if $shopId}{*$bepadoShops[$shopId]->name*}Libri.de Internet GmbH{else}{$sShopname}{/if}
        </h3>
        <hr class="clear">
    </div>
</div>