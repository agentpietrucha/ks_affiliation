{*
 * Copyright since 2007 PrestaShop SA and Contributors
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * https://opensource.org/licenses/OSL-3.0
 *
 * @author    KS Development
 * @copyright Since 2026 KS Development
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 *}
<div class="panel">
    <div class="panel-heading">
        <i class="icon-list"></i>
        {l s='Orders for: %s' sprintf=[$link_description|escape:'htmlall':'UTF-8'] mod='ks_affiliation'}
    </div>
    <p>
        <a href="{$back_url|escape:'htmlall':'UTF-8'}" class="btn btn-default">
            &laquo; {l s='Back to Affiliate Links' mod='ks_affiliation'}
        </a>
    </p>
    {if $orders}
        <table class="table ks-orders-table">
            <thead>
                <tr>
                    <th>{l s='Order ID' mod='ks_affiliation'}</th>
                    <th>{l s='Reference' mod='ks_affiliation'}</th>
                    <th>{l s='Total Paid' mod='ks_affiliation'}</th>
                    <th>{l s='Date' mod='ks_affiliation'}</th>
                    <th>{l s='Action' mod='ks_affiliation'}</th>
                </tr>
            </thead>
            <tbody>
                {foreach $orders as $order}
                    <tr>
                        <td>{$order.id_order|intval}</td>
                        <td>{$order.reference|escape:'htmlall':'UTF-8'}</td>
                        <td>{$order.total_paid|escape:'htmlall':'UTF-8'}</td>
                        <td>{$order.date_add|escape:'htmlall':'UTF-8'}</td>
                        <td>
                            <a href="{$order.order_url|escape:'htmlall':'UTF-8'}"
                               class="btn btn-default btn-xs" target="_blank" rel="noopener">
                                <i class="icon-search-plus"></i>
                                {l s='View Order' mod='ks_affiliation'}
                            </a>
                        </td>
                    </tr>
                {/foreach}
            </tbody>
        </table>
    {else}
        <p class="alert alert-info">
            {l s='No orders tracked for this link yet.' mod='ks_affiliation'}
        </p>
    {/if}
</div>
