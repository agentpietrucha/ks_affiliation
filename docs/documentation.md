# KS Affiliation — Documentation

## Overview

KS Affiliation provides affiliate link tracking inside PrestaShop 8. Each link has a unique 12-character hex token. When a visitor clicks an affiliate URL, a cookie is set; if the visitor places an order during the cookie lifetime, that order is recorded against the link.

## Data model

- `ks_affiliation_link` — one row per affiliate link (`token`, `description`, `active`, `deleted`, timestamps).
- `ks_affiliation_order` — one row per tracked order (`id_order` unique, `id_ks_affiliation_link`).

## Hooks

- `actionValidateOrder` — reads the `ks_affiliation_token` cookie and inserts a tracking row.
- `displayBackOfficeHeader` — loads the back-office CSS/JS only on the module's admin page.

## Front controller

`/module/ks_affiliation/redirect?token=<12-hex>` — validates the token, sets the cookie, and redirects to the homepage. Silently redirects on invalid tokens.

## Configuration

- `KS_AFFILIATION_COOKIE_LIFETIME` — cookie lifetime in days (default `30`).

## Soft delete

Deleting a link sets `deleted = 1` and `active = 0`. Historical order tracking remains intact.
