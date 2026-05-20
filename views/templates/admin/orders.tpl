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
        <div class="row" style="margin-bottom:15px;">
            <div class="col-md-3">
                <div class="panel" style="padding:10px;">
                    <strong>{l s='Total completed orders amount' mod='ks_affiliation'}</strong><br>
                    <span style="font-size:18px;">{$total_completed|escape:'htmlall':'UTF-8'}</span>
                    <small class="text-muted">{l s='(excl. shipping)' mod='ks_affiliation'}</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="panel" style="padding:10px;">
                    <strong>{l s='Total orders amount' mod='ks_affiliation'}</strong><br>
                    <span style="font-size:18px;">{$total_orders|escape:'htmlall':'UTF-8'}</span>
                    <small class="text-muted">{l s='(excl. shipping)' mod='ks_affiliation'}</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="panel" style="padding:10px;">
                    <strong>{l s='Total returns amount' mod='ks_affiliation'}</strong><br>
                    <span style="font-size:18px;">{$total_returns|escape:'htmlall':'UTF-8'}</span>
                    <small class="text-muted">{l s='(excl. shipping)' mod='ks_affiliation'}</small>
                </div>
            </div>
            {if $has_payout}
                <div class="col-md-3">
                    <div class="panel" style="padding:10px;">
                        <strong>{l s='Total payout' mod='ks_affiliation'}</strong><br>
                        <span style="font-size:18px;">{$total_payout|escape:'htmlall':'UTF-8'}</span>
                        <small class="text-muted">{l s='at' mod='ks_affiliation'} {$payout_percentage|escape:'htmlall':'UTF-8'}</small>
                    </div>
                </div>
            {/if}
        </div>
    {/if}
    {if $orders}
        <table class="table ks-orders-table">
            <thead>
                <tr>
                    <th>{l s='Order ID' mod='ks_affiliation'}</th>
                    <th>{l s='Reference' mod='ks_affiliation'}</th>
                    <th>{l s='Total Paid' mod='ks_affiliation'}</th>
                    <th>{l s='Date' mod='ks_affiliation'}</th>
                    <th>{l s='Status' mod='ks_affiliation'}</th>
                    <th>{l s='Finished' mod='ks_affiliation'}</th>
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
                            <span class="ks-order-status"
                                  style="display:inline-block;padding:2px 8px;border-radius:3px;color:#fff;background:{$order.status_color|escape:'htmlall':'UTF-8'};">
                                {$order.status_label|escape:'htmlall':'UTF-8'}
                            </span>
                        </td>
                        <td>
                            <form method="post" action="{$toggle_url|escape:'htmlall':'UTF-8'}" style="margin:0;">
                                <input type="hidden" name="action" value="togglefinished">
                                <input type="hidden" name="id_ks_affiliation_link" value="{$id_link|intval}">
                                <input type="hidden" name="id_ks_affiliation_order" value="{$order.id_ks_affiliation_order|intval}">
                                <input type="checkbox" name="finished"
                                       onchange="this.form.submit();"
                                       {if $order.finished}checked="checked"{/if}>
                            </form>
                        </td>
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
