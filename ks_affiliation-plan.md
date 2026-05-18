# ks_affiliation — Implementation Plan

> Generated for use with `/ps-build ks_affiliation-plan.md`

---

## 1. Module Identity

| Field | Value |
|-------|-------|
| Folder / class / file name | `ks_affiliation` |
| Version | `1.0.0` |
| PS compatibility | min `8.0.0` / max `8.9.99` |
| PHP minimum | `8.0` |
| Admin tab | Yes — child of `AdminCatalog` |
| License | OSL-3.0 |
| Author | KS Development |

---

## 2. Feature Summary

1. Admin can open a dedicated back-office tab "Affiliate Links" under the Catalog menu.
2. Admin can create a new affiliate link by clicking "Add new" and filling in a human-readable description. The token (12-char hex, `bin2hex(random_bytes(6))`) is auto-generated on save and is guaranteed unique (retry up to 5 times on collision).
3. Each link generates a public URL: `https://shop.com/module/ks_affiliation/redirect?token=<token>`. This URL is displayed in the list as a copyable field.
4. Admin can activate/deactivate any link via a toggle in the list.
5. Admin can delete a link (soft-delete: sets `active = 0` and `deleted = 1`, keeps order history intact).
6. Admin sees the full list of links with columns: ID, Description, Token, Full URL, Status, Date Created, Orders count.
7. Admin can click "View Orders" on any link row to see a filtered list of orders placed through that link, with a direct link to the native Order detail page in the back office.
8. A visitor who clicks the affiliate URL is silently redirected to the shop homepage. A cookie `ks_affiliation_token` is set for 30 days.
9. When an order is validated, the hook reads the cookie. If a matching active link exists, the order ID and link ID are stored in `ks_affiliation_order`. The cookie is NOT cleared (allows tracking multiple orders from one click session for 30 days).
10. Duplicate order tracking is prevented: a unique key on `id_order` in `ks_affiliation_order` ensures one order maps to at most one link.

---

## 3. Database Schema

### Table: `ks_affiliation_link`

```sql
CREATE TABLE IF NOT EXISTS `_DB_PREFIX_ks_affiliation_link` (
    `id_ks_affiliation_link` INT(11) NOT NULL AUTO_INCREMENT,
    `token`                  VARCHAR(12) NOT NULL,
    `description`            VARCHAR(255) NOT NULL DEFAULT '',
    `active`                 TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
    `deleted`                TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
    `date_add`               DATETIME NOT NULL,
    `date_upd`               DATETIME NOT NULL,
    PRIMARY KEY (`id_ks_affiliation_link`),
    UNIQUE KEY `uq_token` (`token`),
    KEY `idx_active_deleted` (`active`, `deleted`)
) ENGINE=_MYSQL_ENGINE_ DEFAULT CHARSET=utf8mb4;
```

### Table: `ks_affiliation_order`

```sql
CREATE TABLE IF NOT EXISTS `_DB_PREFIX_ks_affiliation_order` (
    `id_ks_affiliation_order` INT(11) NOT NULL AUTO_INCREMENT,
    `id_ks_affiliation_link`  INT(11) NOT NULL,
    `id_order`                INT(11) UNSIGNED NOT NULL,
    `date_add`                DATETIME NOT NULL,
    PRIMARY KEY (`id_ks_affiliation_order`),
    UNIQUE KEY `uq_order` (`id_order`),
    KEY `idx_link` (`id_ks_affiliation_link`)
) ENGINE=_MYSQL_ENGINE_ DEFAULT CHARSET=utf8mb4;
```

### Configuration keys

| Key | Default | Meaning |
|-----|---------|---------|
| `KS_AFFILIATION_COOKIE_LIFETIME` | `30` | Cookie lifetime in days |

---

## 4. File Tree

```
ks_affiliation/
├── ks_affiliation.php                              # [Phase 1] Main module class
├── controllers/
│   ├── admin/
│   │   ├── index.php                               # [Phase 1] Directory guard
│   │   └── AdminKsAffiliationController.php        # [Phase 1] Back-office CRUD + orders view
│   └── front/
│       ├── index.php                               # [Phase 2] Directory guard
│       └── redirect.php                            # [Phase 2] Click handler — sets cookie, redirects
├── docs/
│   ├── index.php                                   # [Phase 1] Directory guard
│   └── documentation.md                            # [Phase 1] User-facing documentation
├── tests/
│   ├── index.php                                   # [Phase 1] Directory guard
│   ├── Unit/
│   │   ├── index.php                               # [Phase 1] Directory guard
│   │   └── KsAffiliationTokenTest.php              # [Phase 1] Token generation unit test
│   └── Integration/
│       ├── index.php                               # [Phase 1] Directory guard
│       └── KsAffiliationInstallTest.php            # [Phase 1] Install/uninstall integration test
├── upgrade/
│   └── index.php                                   # [Phase 1] Directory guard
├── views/
│   ├── index.php                                   # [Phase 1] Directory guard
│   ├── css/
│   │   ├── index.php                               # [Phase 1] Directory guard
│   │   └── admin.css                               # [Phase 1] Back-office styles
│   ├── js/
│   │   ├── index.php                               # [Phase 1] Directory guard
│   │   └── admin.js                                # [Phase 1] Copy-to-clipboard for link URLs
│   └── templates/
│       ├── index.php                               # [Phase 1] Directory guard
│       └── admin/
│           ├── index.php                           # [Phase 1] Directory guard
│           ├── list.tpl                            # [Phase 1] Links list (overrides default list)
│           ├── orders.tpl                          # [Phase 1] Orders for a single link
│           └── helpers/
│               ├── index.php                       # [Phase 1] Directory guard
│               └── form/
│                   ├── index.php                   # [Phase 1] Directory guard
│                   └── form.tpl                    # [Phase 1] Add/edit link form
├── .gitignore
├── .htaccess
├── index.php                                       # [Phase 1] Module root directory guard
├── phpunit.xml
└── README.md
```

---

## 5. Hooks

| Hook name | Registered in install() | Context guard | What it does |
|-----------|------------------------|---------------|--------------|
| `actionValidateOrder` | Yes | None needed (runs on every validated order) | Reads `ks_affiliation_token` cookie; if valid active link found, inserts row into `ks_affiliation_order`; ignores duplicate (unique key) |
| `displayBackOfficeHeader` | Yes | `controller_name === 'AdminKsAffiliation'` | Adds `admin.css` and `admin.js` to the back-office head |

No front-office display hooks are needed — the module uses a front controller for the redirect.

---

## 6. Admin Tab Registration

```php
private function installTab(): bool
{
    $tab = new Tab();
    $tab->class_name = 'AdminKsAffiliation';
    $tab->module     = $this->name;
    $tab->id_parent  = (int) Tab::getIdFromClassName('AdminCatalog');
    $tab->icon       = ''; // no custom icon per spec

    foreach (Language::getLanguages() as $lang) {
        $tab->name[$lang['id_lang']] = 'Affiliate Links';
    }

    return (bool) $tab->add();
}

private function uninstallTab(): bool
{
    $id_tab = (int) Tab::getIdFromClassName('AdminKsAffiliation');

    if ($id_tab === 0) {
        return true;
    }

    $tab = new Tab($id_tab);

    return (bool) $tab->delete();
}
```

---

## 7. File-by-File Specification

### `ks_affiliation.php`

- **Purpose:** Module entry point; handles install/uninstall, hook callbacks, and tab management.
- **Class:** `KsAffiliation extends Module`
- **Key methods:**

| Method | Signature | Description |
|--------|-----------|-------------|
| `__construct` | `void` | Sets all module metadata and `ps_versions_compliancy`. |
| `install` | `bool` | Chains: `parent::install()`, `installTab()`, `registerHook('actionValidateOrder')`, `registerHook('displayBackOfficeHeader')`, `installDb()`, `Configuration::updateValue('KS_AFFILIATION_COOKIE_LIFETIME', 30)`. Returns false on first failure. |
| `uninstall` | `bool` | Chains: `parent::uninstall()`, `uninstallTab()`, `uninstallDb()`, `Configuration::deleteByName('KS_AFFILIATION_COOKIE_LIFETIME')`. |
| `installDb` | `private bool` | Executes both `CREATE TABLE IF NOT EXISTS` statements. |
| `uninstallDb` | `private bool` | Executes both `DROP TABLE IF EXISTS` statements. |
| `installTab` | `private bool` | Creates `AdminKsAffiliation` tab as child of `AdminCatalog`. |
| `uninstallTab` | `private bool` | Deletes the tab by class name. |
| `hookActionValidateOrder` | `void` | Reads cookie `ks_affiliation_token`. Looks up active, non-deleted link by token. If found, attempts `Db::getInstance()->insert('ks_affiliation_order', [...])` wrapped in try/catch to silently ignore duplicate key errors. |
| `hookDisplayBackOfficeHeader` | `string` | Guards on `controller_name === 'AdminKsAffiliation'`. Registers `admin.css` and `admin.js`. Returns `''`. |
| `generateToken` | `private string` | Calls `bin2hex(random_bytes(6))`. Checks uniqueness against DB. Retries up to 5 times. Throws `\RuntimeException` if all attempts collide. |

---

### `controllers/admin/AdminKsAffiliationController.php`

- **Purpose:** Full CRUD for affiliate links + a nested "view orders" screen.
- **Class:** `AdminKsAffiliationController extends ModuleAdminController`
- **Key methods:**

| Method | Signature | Description |
|--------|-----------|-------------|
| `__construct` | `void` | Sets `$this->table = 'ks_affiliation_link'`, `$this->className = 'KsAffiliationLink'` (dummy — we manage DB directly), `$this->bootstrap = true`, `$this->identifier = 'id_ks_affiliation_link'`. Defines `$this->fields_list` with columns: ID, Description, Token, URL (computed), Active, Date Add, Orders (computed via sub-query). Calls `parent::__construct()`. |
| `renderList` | `string` | Overrides to inject the computed URL column and Orders count. Uses a JOIN with `ks_affiliation_order` to count orders per link. Excludes `deleted = 1` rows via `$this->_where`. |
| `renderForm` | `string` | Returns a `HelperForm` with fields: `description` (text, required), `active` (switch). Token is not shown in the form (auto-generated on save). |
| `processSave` | `void` | Validates CSRF token. Validates `description` is non-empty, max 255 chars. Calls `$this->module->generateToken()`. Inserts row into `ks_affiliation_link`. On success, redirects back to list with success message. |
| `processDelete` | `void` | Sets `deleted = 1`, `active = 0`, `date_upd = NOW()` for the given ID. Does NOT hard-delete to preserve order history. |
| `processToggleActive` | `void` | Flips the `active` flag for the given ID. Sets `date_upd = NOW()`. |
| `initContent` | `void` | Checks for `action=vieworders&id_ks_affiliation_link=N`. If present, renders `orders.tpl` instead of the standard list. Otherwise calls `parent::initContent()`. |
| `renderOrdersView` | `string` | Queries `ks_affiliation_order` JOIN `orders` for the given link ID. Assigns to Smarty: `link_description`, `orders` array (id_order, reference, total_paid, date_add, order_url). Renders `views/templates/admin/orders.tpl`. |

- **Smarty variables for `list.tpl`:** Standard HelperList variables; no custom assigns needed beyond what `renderList()` produces.
- **Smarty variables for `orders.tpl`:** `link_description` (string, escaped), `orders` (array of assoc arrays), `back_url` (link to main list).
- **Security notes:** Verify `Tools::getValue('token')` === `Tools::getAdminTokenLite('AdminKsAffiliation')` in `processSave()` and `processDelete()`. Cast all ID inputs with `(int)`.

---

### `controllers/front/redirect.php`

- **Purpose:** Handles an affiliate link click. Sets a tracking cookie and redirects to homepage.
- **Class:** `KsAffiliationRedirectModuleFrontController extends ModuleFrontController`
- **Key methods:**

| Method | Signature | Description |
|--------|-----------|-------------|
| `initContent` | `void` | Reads `token` from GET (cast to string, max 12 chars, alphanumeric only via `preg_match`). Looks up `ks_affiliation_link` by token WHERE `active=1` AND `deleted=0`. If not found, redirects to homepage silently. If found, calls `setAffiliateCookie($token)`. Then calls `Tools::redirect($this->context->link->getPageLink('index'))`. |
| `setAffiliateCookie` | `private void` | Sets a cookie named `ks_affiliation_token` with the token value. Lifetime: `time() + (int) Configuration::get('KS_AFFILIATION_COOKIE_LIFETIME') * 86400`. Path: `/`. Uses `setcookie()` with `httponly: true` and `samesite: Lax`. |

- **Security notes:** Token input must match `/^[a-f0-9]{12}$/` exactly. No output rendered — always redirects. No AJAX.

---

### `views/templates/admin/orders.tpl`

- **Used by:** `AdminKsAffiliationController::renderOrdersView()`
- **Variables:**

| Variable | Type | Escaped how |
|----------|------|-------------|
| `$link_description` | string | `\|escape:'htmlall':'UTF-8'` |
| `$orders` | array | Each field escaped inline |
| `$back_url` | string | `\|escape:'htmlall':'UTF-8'` |

- **UI elements:**
  - Page title: "Orders for: [link_description]"
  - "Back to Affiliate Links" button linking to `$back_url`
  - Table: columns = Order ID, Reference, Total Paid, Date, Action
  - Action column: "View Order" link using `$order.order_url` (the native PS back-office order URL)
  - Empty state: "No orders tracked for this link yet."

---

### `views/templates/admin/helpers/form/form.tpl`

- **Used by:** `AdminKsAffiliationController::renderForm()` (standard HelperForm override location)
- **UI elements:** Standard HelperForm rendering. Description field (text input). Active toggle (switch). Token field is READ-ONLY if editing (shown as plain text, not input). "Save" button.

---

### `views/js/admin.js`

- **Purpose:** Copy-to-clipboard for the affiliate URL shown in the list.
- **Implementation:** Attaches a click handler to `.ks-copy-url` buttons. Uses `navigator.clipboard.writeText()`. Shows a brief inline "Copied!" confirmation. No external libraries.

---

### `views/css/admin.css`

- **Purpose:** Minor back-office styles — highlight the URL column, style the copy button, ensure the orders table matches the PS8 back-office aesthetic.

---

## 8. Template Specification

### `views/templates/admin/orders.tpl`

```smarty
{* license header *}
<div class="panel">
    <div class="panel-heading">
        {l s='Orders for: %s' sprintf=[$link_description|escape:'htmlall':'UTF-8'] mod='ks_affiliation'}
    </div>
    <a href="{$back_url|escape:'htmlall':'UTF-8'}" class="btn btn-default">
        &laquo; {l s='Back to Affiliate Links' mod='ks_affiliation'}
    </a>
    {if $orders}
        <table class="table">
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
                           class="btn btn-default btn-xs" target="_blank">
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
```

---

## 9. Affiliate Link Flow

### A. Link Creation (Admin)

1. Admin opens **Catalog > Affiliate Links** in back office.
2. Admin clicks **Add new affiliate link**.
3. `AdminKsAffiliationController::renderForm()` displays: description field, active toggle.
4. Admin fills description, clicks Save.
5. POST hits `AdminKsAffiliationController::processSave()`.
6. Token verified via `Tools::getAdminTokenLite('AdminKsAffiliation')`.
7. Description validated (non-empty, ≤ 255 chars).
8. `$this->module->generateToken()` generates a 12-char hex string, checks `ks_affiliation_link.token` for uniqueness (up to 5 retries).
9. Row inserted: `{token, description, active=1, deleted=0, date_add=NOW(), date_upd=NOW()}`.
10. Admin redirected to list. Full affiliate URL shown in list as: `{shop_base_url}/module/ks_affiliation/redirect?token={token}`.

### B. Customer Clicks Affiliate Link

1. Visitor receives and clicks: `https://shop.com/module/ks_affiliation/redirect?token=abc123def456`.
2. `KsAffiliationRedirectModuleFrontController::initContent()` runs.
3. Token extracted: `preg_match('/^[a-f0-9]{12}$/', Tools::getValue('token'))`.
4. DB query: `SELECT id_ks_affiliation_link FROM ks_affiliation_link WHERE token = '{token}' AND active = 1 AND deleted = 0`.
5. If no match → `Tools::redirect(homepage)` silently.
6. If match → `setcookie('ks_affiliation_token', $token, time() + 30*86400, '/', '', true, true)`.
7. `Tools::redirect($this->context->link->getPageLink('index'))`.
8. Visitor lands on homepage. Cookie `ks_affiliation_token` active for 30 days.

### C. Customer Places an Order

1. Customer completes checkout. PrestaShop fires `actionValidateOrder`.
2. `KsAffiliation::hookActionValidateOrder(array $params)` runs.
3. `$token = isset($_COOKIE['ks_affiliation_token']) ? $_COOKIE['ks_affiliation_token'] : ''`.
4. If empty → return immediately.
5. Validate token format: `preg_match('/^[a-f0-9]{12}$/', $token)` → if invalid, return.
6. Look up `id_ks_affiliation_link` WHERE `token = pSQL($token)` AND `active=1` AND `deleted=0`.
7. If no link found → return.
8. `$id_order = (int) $params['order']->id`.
9. Attempt: `Db::getInstance()->insert('ks_affiliation_order', ['id_ks_affiliation_link' => $id_link, 'id_order' => $id_order, 'date_add' => date('Y-m-d H:i:s')])`.
10. If duplicate key error (order already tracked) → silently ignore (wrap in try/catch or check `Db::getInstance()->getNumberError()`).
11. Cookie NOT cleared — customer can make additional orders within the 30-day window.

### D. Admin Views Orders for a Link

1. Admin is on the Affiliate Links list.
2. Admin clicks **View Orders** button in a row (links to `?action=vieworders&id_ks_affiliation_link=N&token=...`).
3. `AdminKsAffiliationController::initContent()` detects `action=vieworders`.
4. `renderOrdersView()` runs:
   - Fetches link description for the given ID.
   - Queries: `SELECT o.id_order, o.reference, o.total_paid, o.date_add FROM ks_affiliation_order kao JOIN ps_orders o ON o.id_order = kao.id_order WHERE kao.id_ks_affiliation_link = {id}`.
   - Builds `order_url` for each: `$this->context->link->getAdminLink('AdminOrders') . '&id_order=' . (int)$row['id_order'] . '&vieworder'`.
5. Assigns to Smarty, renders `orders.tpl`.

---

## 10. Implementation Order

### Group A — Skeleton (install/uninstall cleanly, tab appears)

- [ ] `index.php` (root)
- [ ] `ks_affiliation.php` — constructor, `install()`, `uninstall()`, `installDb()`, `uninstallDb()`, `installTab()`, `uninstallTab()`, `generateToken()` stub (returns hardcoded string for now)
- [ ] `controllers/admin/index.php`
- [ ] `controllers/front/index.php`
- [ ] `views/index.php` + all sub-`index.php` guards
- [ ] `docs/index.php`
- [ ] `tests/index.php` + sub-guards
- [ ] `upgrade/index.php`
- [ ] `.htaccess`, `.gitignore`
- [ ] `phpunit.xml`
- [ ] `README.md`, `docs/documentation.md`

**Verify:** Module installs without errors. "Affiliate Links" tab appears under Catalog.

### Group B — Admin Dashboard (create, list, toggle, delete links)

- [ ] `controllers/admin/AdminKsAffiliationController.php` — full implementation
- [ ] `views/css/admin.css`
- [ ] `views/js/admin.js`
- [ ] `views/templates/admin/helpers/form/form.tpl`
- [ ] `generateToken()` — real implementation with uniqueness retry loop

**Verify:** Can create a link, see it in the list with URL, toggle active/inactive, soft-delete.

### Group C — Front Controller (cookie tracking)

- [ ] `controllers/front/redirect.php`

**Verify:** Visit affiliate URL → cookie set in browser → redirect to homepage. Invalid token → redirect silently.

### Group D — Order Tracking (orders appear in dashboard)

- [ ] `hookActionValidateOrder` in `ks_affiliation.php` — full implementation
- [ ] `AdminKsAffiliationController::renderOrdersView()` + `initContent()` override
- [ ] `views/templates/admin/orders.tpl`

**Verify:** Place a test order with cookie set → order appears under View Orders for the correct link.

### Group E — Polish

- [ ] `hookDisplayBackOfficeHeader` — loads CSS/JS only on admin affiliate page
- [ ] Copy-to-clipboard in `admin.js`
- [ ] Empty-state messages in templates
- [ ] `KsAffiliationTokenTest.php` unit test
- [ ] `KsAffiliationInstallTest.php` integration test stub

---

## 11. Edge Cases & Validation Rules

- **Token collision on insert:** `generateToken()` retries up to 5 times. On the 5th failure, throw `\RuntimeException('KsAffiliation: could not generate unique token after 5 attempts')`. This is caught in `processSave()` and shown as an admin error.
- **Order already tracked (duplicate `id_order`):** The `UNIQUE KEY uq_order (id_order)` on `ks_affiliation_order` prevents double-tracking. The hook wraps the insert in a try/catch and silently ignores the duplicate key error (error code 1062). It does NOT re-assign the order to a new link.
- **Link deactivated after cookie is set:** The hook checks `active = 1 AND deleted = 0` at order time. A deactivated link will not receive new orders even if the customer has the cookie.
- **Cookie tampered with (invalid format):** `preg_match('/^[a-f0-9]{12}$/', $token)` gates all use of cookie value. Any non-matching value is discarded without DB query.
- **View Orders with no orders:** `orders.tpl` handles empty `$orders` array with an info-level alert message.
- **Soft-delete preserves history:** `processDelete()` sets `deleted = 1` and `active = 0`. The `ks_affiliation_order` rows remain and are still visible via View Orders. The link no longer accepts new clicks or order tracking.
- **Description empty on save:** `processSave()` checks `Tools::getValue('description')` is non-empty after `trim()`. Adds an error and re-renders the form if empty.
- **Redirect to invalid token URL:** If token is missing, empty, wrong format, or points to a deleted/inactive link, the front controller redirects to the homepage with no error shown to the visitor.
- **Multiple orders from same customer:** Each order fires `actionValidateOrder` independently. Each insert is attempted independently. Duplicates are silently skipped. Cookie persists for all 30 days.
- **PHP `setcookie()` headers already sent:** `redirect.php` must not emit any output before calling `setcookie()`. The front controller must not call `parent::initContent()` before the cookie and redirect.

---

## 12. Out of Scope

The following are explicitly NOT part of this implementation:

- Front-office affiliate dashboard for affiliates (no customer-facing account page)
- Commission calculation or payout tracking
- Affiliate user roles or registration
- Click-count analytics or graph views
- Multi-shop (multishop) support
- Email notifications on order conversion
- Expiry dates on individual affiliate links
- UTM parameter injection
- Export to CSV
- REST API endpoints
