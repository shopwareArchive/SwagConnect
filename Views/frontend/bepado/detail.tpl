{block name='frontend_detail_buy_button'}
{if $bepadoProduct}
    <strong>Marktplatz Artikel von {$bepadoShop->name}</strong>
{/if}
    {$smarty.block.parent}
{if $bepadoProduct}
    <div>
        bla bla blaaa
    </div>
{/if}
{/block}