# KS Affiliation

Affiliate link tracking for PrestaShop 8. Generate per-store tracking URLs, set a visitor cookie on click, attribute completed orders, and review per-link earnings.

## Prerequisites

- PrestaShop 8.0 – 8.9.x
- PHP 8.0+
- Multi-store compatible

## Installation

1. Upload the `ks_affiliation/` folder to `/modules/`
2. Go to **Modules > Module Manager** and search for "KS Affiliation"
3. Click **Install**

## Configuration

Two configuration surfaces:

### 1. Global (Modules > Module Manager > KS Affiliation > Configure)

| Setting | Description |
|---------|-------------|
| Order completed status | Pick the order state that represents a final, paid, shipped order. Drives the per-order status column in the View Orders screen. |

The page also shows the value of **Customer Service > Merchandise Returns > Time limit of validity** for reference — that value is used to decide when an order graduates from *Awaiting* to *Completed*.

### 2. Per affiliate link (Catalog > Affiliate Links > Add new / Edit)

| Field | Required | Notes |
|-------|----------|-------|
| Description | Yes | Internal label, max 255 chars. |
| Cookie lifespan (days) | Yes | 1–3650. How long the tracking cookie persists after a click. Per-link, no global default. |
| Payout percentage | No | 0–100. Used to compute the *Total payout* summary in the orders view. Hidden when empty/zero. |
| Active | Yes | Soft on/off. Inactive links do nothing on click and do not attribute new orders. |
| Affiliate code | Optional on create | Alphanumeric, 3–64 chars. Type your own (e.g. `SummerSale2026`) or click **Generate** for a random 12-char code. Leave empty to auto-generate. Globally unique. Locked once the link is created. |

## Usage

### Sharing an affiliate URL

Any URL on the store with `?affiliate_token=<CODE>` works:

- `https://shop-a.example.com/?affiliate_token=SummerSale2026`
- `https://shop-a.example.com/some-product?affiliate_token=abcd1234ef56`

The code is the value entered (or auto-generated) on the affiliate link. Allowed characters: letters and digits, 3–64 long.

The legacy `/module/ks_affiliation/redirect?token=<TOKEN>` URL also works.

On click the module:
1. Sets a `ks_affiliation_token` cookie (`httponly`, `SameSite=Lax`, lifetime per the link's setting).
2. Strips `affiliate_token` from the address bar (server 302 + JS `history.replaceState` fallback).
3. Leaves the visitor on whatever page they requested.

### Multi-store

Each affiliate link belongs to exactly one shop. A token created on **Store A** only sets the cookie when visited on Store A's domain, and only attributes orders placed on Store A. The same token does nothing on Store B. To track Store B traffic, create a new link while Store B is selected in the multi-store header.

The admin list is scoped to the currently selected shop / group / "All shops" context.

### Order attribution

When an order is validated, the module:
1. Reads the `ks_affiliation_token` cookie.
2. Looks up an active, non-deleted link with that token **on the same shop** as the order.
3. Inserts a row into `ks_affiliation_order` (one order ↔ one link, enforced by a unique key).
4. Leaves the cookie in place so subsequent orders within the lifespan are also attributed.

### View Orders

Click **View Orders** on any link to see:

- Total orders amount (sum of `total_products_wt` — products with tax, **excluding shipping**).
- Total payout (only shown when the link has a non-zero payout %).
- A per-order row with reference, total paid, date, status badge, and a *View Order* button that opens the PS8 order detail page in a new tab.

### Order status badge

For each tracked order:

| Badge | When |
|-------|------|
| **Returned** (red) | The order has any record in `order_return`. |
| **Completed** (green) | The order has reached the configured *Order completed* state, and the time since that state was set ≥ Merchandise Returns time limit. |
| **Awaiting** (gray) | Anything else — including orders that haven't reached the completed state yet, and orders within the return window. |

## Admin actions per link

- **View Orders** — opens the orders view for the link.
- **Edit** — change description, cookie lifespan, payout %, active flag. Affiliate code is preserved (not editable after creation).
- **Delete** — soft delete (sets `active = 0`, `deleted = 1`). Historical attributions remain visible.

## Data model

- `ks_affiliation_link` — link definitions. Globally unique `token`. Scoped by `id_shop`.
- `ks_affiliation_order` — `id_order` ↔ `id_ks_affiliation_link` mapping. Unique on `id_order`.

Both tables are dropped on uninstall.

## Hooks

| Hook | Purpose |
|------|---------|
| `actionDispatcher` | Reads `affiliate_token` from any front-office URL, sets cookie, strips param via redirect. |
| `actionValidateOrder` | Inserts attribution row when an order is validated. |
| `displayHeader` | JS fallback to strip `affiliate_token` from the address bar. |
| `displayBackOfficeHeader` | Loads admin CSS/JS on the affiliate links page (also loaded directly via `setMedia`). |

## Versioning

Current version: **1.0.3**. Upgrade scripts in `upgrade/` handle schema migrations between releases (idempotent).

## New features nice to have
- [x] Custom `affiliate_token` — admin can type a custom alphanumeric code (3–64 chars) on create, or click *Generate* for a random one.

## Known bugs
- Affiliate URL Copy button doesn't always work
- Editing existing affiliate link doesn't work. New entry is created instead of editing the existing
