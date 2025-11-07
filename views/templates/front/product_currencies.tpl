<div class="product-currency-rates">
    <h3>{l s='Price in Other Currencies' mod='currencyrate'}</h3>
    {if $converted_prices && !empty($converted_prices)}
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                <tr>
                    <th>{l s='Currency' mod='currencyrate'}</th>
                    <th>{l s='Price' mod='currencyrate'}</th>
                    <th>{l s='Exchange Rate' mod='currencyrate'}</th>
                </tr>
                </thead>
                <tbody>
                {foreach $converted_prices as $price}
                    <tr>
                        <td><strong>{$price.currency_code}</strong> - {$price.currency_name}</td>
                        <td>{$price.converted_price|number_format:2} {$price.currency_code}</td>
                        <td>1 PLN = {$price.rate|number_format:4} {$price.currency_code}</td>
                    </tr>
                {/foreach}
                </tbody>
            </table>
        </div>
        <p class="text-muted small">
            {l s='Base price:' mod='currencyrate'} {$product_price|number_format:2} PLN
            <br>
            {l s='Rates updated daily from NBP' mod='currencyrate'}
        </p>
    {else}
        <p class="alert alert-warning">
            {l s='Currency rates are not currently available.' mod='currencyrate'}
        </p>
    {/if}
</div>