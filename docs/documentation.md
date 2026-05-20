# KS Affiliation — Documentation

## Overview

KS Affiliation provides affiliate link tracking inside PrestaShop 8. Each link has a unique alphanumeric token (3–64 chars; auto-generated or user-supplied). When a visitor clicks an affiliate URL, a cookie is set; if the visitor places an order during the cookie lifetime, that order is recorded against the link.

## Data model

- `ks_affiliation_link` — one row per affiliate link (`token`, `description`, `cookie_lifetime_days`, `payout_percentage`, `active`, `deleted`, timestamps).
- `ks_affiliation_order` — one row per tracked order (`id_order` unique, `id_ks_affiliation_link`, `finished` flag, `date_add`).

## Hooks

- `actionDispatcher` — reads `affiliate_token` from any front-office URL, sets the cookie, strips the param via 302.
- `actionValidateOrder` — reads the `ks_affiliation_token` cookie and inserts a tracking row.
- `displayHeader` — JS fallback to strip `affiliate_token` from the address bar / URL fragment.
- `displayBackOfficeHeader` — loads the back-office CSS/JS only on the module's admin page.

## Front controller

`/module/ks_affiliation/redirect?token=<TOKEN>` — legacy entry point. Validates the token, sets the cookie, and redirects. Silently redirects on invalid tokens.

## Configuration keys

- `KS_AFFILIATION_COOKIE_LIFETIME` — fallback cookie lifetime in days (default `30`). Per-link `cookie_lifetime_days` takes precedence.
- `KS_AFFILIATION_COMPLETED_STATE` — order state that represents a finalized order.
- `KS_AFFILIATION_COMPLETED_DELAY` — extra days on top of `PS_ORDER_RETURN_NB_DAYS` before an order graduates to *Completed*.

## Status badge

Per-line *effective returned quantity* = `max(SUM(order_return_detail.product_quantity), order_detail.product_quantity_refunded)`, clamped to `order_detail.product_quantity`.

| Badge | Condition |
|-------|-----------|
| Returned | All ordered units returned/refunded. |
| Partially Completed | Some units returned/refunded. |
| Completed | No returns. Reached configured completed state. `days_since ≥ PS_ORDER_RETURN_NB_DAYS + KS_AFFILIATION_COMPLETED_DELAY`. |
| Awaiting | Anything else. |

## View Orders dashboard

All amounts exclude shipping; product values are tax-included.

- **Total completed orders amount** — sum of kept value across orders with *Completed* status.
- **Total orders amount** — gross sum of `total_products_wt`.
- **Total returns amount** — sum of returned/refunded line-item values.
- **Total payout** — `payout_percentage × Total completed orders amount`.

The per-row *Total Paid* column shows the order's kept product value (`total_products_wt - returned_value`).

## Manual flags

- `ks_affiliation_order.finished` — toggled by a checkbox in the View Orders table. Persistent; no effect on status badge or totals.

## Soft delete

Deleting a link sets `deleted = 1` and `active = 0`. Historical order tracking remains intact.
