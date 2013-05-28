{block name='frontend_detail_buy_button'}
{if $bepadoProduct}
    <style>
        #buybox #basketButton {
            display: block;
            margin: auto;
            width: 250px;
        }
    </style>
    <div class="bepado_detail_buy_button" style="background-color: #eee;
    border-color: #ccc;
    border-radius: 5px 5px 5px 5px;
    border-style: solid;
    border-width: 1px;
    padding: 15px;
    display: block;">
{/if}
    {$smarty.block.parent}
{if $bepadoProduct}
    <strong style="color: #333;display: block; padding: 15px 0 2px 10px;">Marktplatz Artikel von {*$bepadoShop->name*}Libri.de Internet GmbH</strong>
    <p style="padding-left: 10px;">
        Die Versandkosten für diesen Artikel ...
    </p>
    <div>
{/if}
{/block}