{*
 * Get Free Shipping PrestaShop module.
 *
 * @author    Niels Wouda <n.wouda@apium.nl>
 * @copyright 2016, Apium
 * @license   Academic Free License (AFL 3.0) <http://opensource.org/licenses/afl-3.0.php>
 *}
{if $remaining > 0 && ($remaining_threshold == 0 || $remaining < $remaining_threshold)}
    <div class="block_get_free_shipping text-right">
        <p style="color:#090;">
            <strong>
                {l s='Spend another' mod='getfreeshipping'}
                {convertPrice price=$remaining}
                {l s='to get free shipping for your order!' mod='getfreeshipping'}
            </strong>
        </p>
    </div>
{/if}