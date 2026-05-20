<?php
/**
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
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminKsAffiliationController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap   = true;
        $this->table       = 'ks_affiliation_link';
        $this->identifier  = 'id_ks_affiliation_link';
        $this->className   = 'KsAffiliationLink';
        $this->lang        = false;

        parent::__construct();

        $this->list_no_link = true;

        $this->addRowAction('vieworders');
        $this->addRowAction('edit');
        $this->addRowAction('delete');

        $this->_where = ' AND a.`deleted` = 0';

        $shopIds = Shop::getContextListShopID();
        if (!empty($shopIds)) {
            $shopIdsCast = array_map('intval', (array) $shopIds);
            $this->_where .= ' AND a.`id_shop` IN (' . implode(',', $shopIdsCast) . ')';
        }

        $this->_select = "a.`token` AS `full_url`,
                          s.`name` AS `shop_name`,
                          (SELECT COUNT(*) FROM `" . _DB_PREFIX_ . "ks_affiliation_order` kao
                          WHERE kao.`id_ks_affiliation_link` = a.`id_ks_affiliation_link`) AS `orders_count`";

        $this->_join = ' LEFT JOIN `' . _DB_PREFIX_ . 'shop` s ON s.`id_shop` = a.`id_shop`';

        $this->fields_list = [
            'id_ks_affiliation_link' => [
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ],
            'description' => [
                'title' => $this->l('Description'),
            ],
            'shop_name' => [
                'title'   => $this->l('Shop'),
                'search'  => false,
                'orderby' => false,
            ],
            'token' => [
                'title' => $this->l('Token'),
                'class' => 'fixed-width-md',
            ],
            'full_url' => [
                'title'           => $this->l('URL'),
                'search'          => false,
                'orderby'         => false,
                'callback'        => 'displayFullUrl',
                'callback_object' => $this,
            ],
            'active' => [
                'title'   => $this->l('Status'),
                'active'  => 'status',
                'type'    => 'bool',
                'align'   => 'center',
                'class'   => 'fixed-width-sm',
                'orderby' => false,
            ],
            'cookie_lifetime_days' => [
                'title' => $this->l('Cookie lifespan (days)'),
                'align' => 'center',
                'class' => 'fixed-width-sm',
                'type'  => 'text',
            ],
            'payout_percentage' => [
                'title'  => $this->l('Payout %'),
                'align'  => 'center',
                'class'  => 'fixed-width-sm',
                'suffix' => '%',
                'type'   => 'decimal',
            ],
            'date_add' => [
                'title' => $this->l('Date created'),
                'type'  => 'datetime',
            ],
            'orders_count' => [
                'title'   => $this->l('Orders'),
                'align'   => 'center',
                'search'  => false,
                'orderby' => false,
            ],
        ];
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);
        $this->addCSS(_MODULE_DIR_ . 'ks_affiliation/views/css/admin.css');
        $this->addJS(_MODULE_DIR_ . 'ks_affiliation/views/js/admin.js');
    }

    public function initContent(): void
    {
        if (Tools::getValue('action') === 'vieworders'
            && (int) Tools::getValue('id_ks_affiliation_link') > 0
        ) {
            $this->display = 'view';
            $this->initToolbar();
            $this->initPageHeaderToolbar();

            $this->content = $this->renderOrdersView((int) Tools::getValue('id_ks_affiliation_link'));

            $this->context->smarty->assign([
                'content'                   => $this->content,
                'show_page_header_toolbar'  => $this->show_page_header_toolbar,
                'page_header_toolbar_title' => $this->page_header_toolbar_title,
                'page_header_toolbar_btn'   => $this->page_header_toolbar_btn,
            ]);

            return;
        }

        parent::initContent();
    }

    public function initToolbar(): void
    {
        parent::initToolbar();
    }

    public function initPageHeaderToolbar(): void
    {
        $this->page_header_toolbar_title = $this->l('Affiliate Links');

        if (empty($this->display)) {
            $this->page_header_toolbar_btn['new'] = [
                'href' => self::$currentIndex . '&add' . $this->table . '&token=' . $this->token,
                'desc' => $this->l('Add new affiliate link'),
                'icon' => 'process-icon-new',
            ];
        }

        parent::initPageHeaderToolbar();
    }

    public function displayFullUrl(string $token, array $row): string
    {
        $id_shop  = isset($row['id_shop']) ? (int) $row['id_shop'] : (int) $this->context->shop->id;
        $base     = $this->context->link->getPageLink('index', true, null, null, false, $id_shop);
        $sep      = (strpos($base, '?') === false) ? '?' : '&';
        $url      = $base . $sep . Ks_affiliation::QUERY_PARAM . '=' . rawurlencode($row['token']);

        $urlAttr   = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
        $copyLabel = htmlspecialchars($this->l('Copy'), ENT_QUOTES, 'UTF-8');

        $inline = "(function(btn){"
                . "var t=btn.getAttribute('data-url');"
                . "var done=function(){var o=btn.innerHTML;btn.innerHTML=" . json_encode($this->l('Copied!')) . ";"
                . "setTimeout(function(){btn.innerHTML=o;},1500);};"
                . "var legacy=function(){var ta=document.createElement('textarea');ta.value=t;"
                . "ta.style.position='fixed';ta.style.opacity='0';document.body.appendChild(ta);"
                . "ta.focus();ta.select();var ok=false;try{ok=document.execCommand('copy');}catch(e){}"
                . "document.body.removeChild(ta);return ok;};"
                . "if(window.isSecureContext&&navigator.clipboard&&navigator.clipboard.writeText){"
                . "navigator.clipboard.writeText(t).then(done,function(){if(legacy())done();});"
                . "}else{if(legacy())done();}"
                . "})(this);return false;";

        $inlineAttr = htmlspecialchars($inline, ENT_QUOTES, 'UTF-8');

        $rendered  = '<div class="input-group">';
        $rendered .= '<input type="text" class="form-control" readonly value="' . $urlAttr . '" onclick="this.select();">';
        $rendered .= '<span class="input-group-btn">';
        $rendered .= '<button type="button" class="btn btn-default" data-url="' . $urlAttr . '" onclick="' . $inlineAttr . '">'
            . $copyLabel . '</button>';
        $rendered .= '</span></div>';

        return $rendered;
    }

    public function displayViewordersLink(string $token, int $id): string
    {
        $href = self::$currentIndex
            . '&action=vieworders&id_ks_affiliation_link=' . (int) $id
            . '&token=' . $this->token;

        return '<a class="btn btn-default" href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '">'
            . '<i class="icon-list"></i> '
            . htmlspecialchars($this->l('View Orders'), ENT_QUOTES, 'UTF-8')
            . '</a>';
    }

    public function renderForm(): string
    {
        $id = (int) Tools::getValue('id_ks_affiliation_link');

        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Affiliate link'),
                'icon'  => 'icon-link',
            ],
            'input' => [
                [
                    'type'  => 'free',
                    'label' => '',
                    'name'  => 'id_ks_affiliation_link_raw',
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l('Description'),
                    'name'     => 'description',
                    'required' => true,
                    'maxlength' => 255,
                    'hint'     => $this->l('Internal label to identify this link.'),
                ],
                [
                    'type'      => 'text',
                    'label'     => $this->l('Cookie lifespan (days)'),
                    'name'      => 'cookie_lifetime_days',
                    'required'  => true,
                    'class'     => 'fixed-width-sm',
                    'suffix'    => $this->l('days'),
                    'hint'      => $this->l('How long the tracking cookie persists after a click. Must be a positive integer.'),
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l('Payout percentage'),
                    'name'     => 'payout_percentage',
                    'required' => false,
                    'class'    => 'fixed-width-sm',
                    'suffix'   => '%',
                    'hint'     => $this->l('Optional. Affiliate payout as a percentage of the order amount (excluding shipping). Example: 10 = 10%. Leave empty to hide payout totals.'),
                ],
                [
                    'type'   => 'switch',
                    'label'  => $this->l('Active'),
                    'name'   => 'active',
                    'values' => [
                        ['id' => 'active_on',  'value' => 1, 'label' => $this->l('Yes')],
                        ['id' => 'active_off', 'value' => 0, 'label' => $this->l('No')],
                    ],
                ],
            ],
            'submit' => ['title' => $this->l('Save')],
        ];

        $row = [];

        if ($id > 0) {
            $row = Db::getInstance()->getRow(
                'SELECT * FROM `' . _DB_PREFIX_ . 'ks_affiliation_link`
                 WHERE `id_ks_affiliation_link` = ' . $id
            ) ?: [];
        }

        $this->fields_value = [
            'id_ks_affiliation_link_raw' => '<input type="hidden" name="id_ks_affiliation_link" value="' . $id . '">',
            'description'                => $row['description'] ?? '',
            'cookie_lifetime_days'       => isset($row['cookie_lifetime_days']) ? (int) $row['cookie_lifetime_days'] : 30,
            'payout_percentage'          => isset($row['payout_percentage']) ? (float) $row['payout_percentage'] : 0,
            'active'                     => isset($row['active']) ? (int) $row['active'] : 1,
            'token_display'              => $row['token'] ?? '',
        ];

        if (!empty($row['token'])) {
            $this->fields_form['input'][] = [
                'type'     => 'free',
                'label'    => $this->l('Token'),
                'name'     => 'token_display',
                'desc'     => $row['token'],
            ];
        } else {
            $tokenHint = $this->l('Optional. Letters and digits only, 3–64 characters. Leave empty to auto-generate.');
            $generateLabel = $this->l('Generate');
            $tokenInputHtml = '<div class="input-group" style="max-width:420px;">'
                . '<input type="text" class="form-control" name="custom_token" value="'
                . htmlspecialchars((string) Tools::getValue('custom_token', ''), ENT_QUOTES, 'UTF-8')
                . '" maxlength="64" pattern="[A-Za-z0-9]{3,64}" autocomplete="off">'
                . '<span class="input-group-btn">'
                . '<button type="button" class="btn btn-default" id="ks-generate-token">'
                . '<i class="icon-refresh"></i> ' . htmlspecialchars($generateLabel, ENT_QUOTES, 'UTF-8')
                . '</button>'
                . '</span>'
                . '</div>'
                . '<p class="help-block">' . htmlspecialchars($tokenHint, ENT_QUOTES, 'UTF-8') . '</p>'
                . '<script>(function(){var b=document.getElementById("ks-generate-token");if(!b)return;'
                . 'b.addEventListener("click",function(){var c="abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";'
                . 'var n=12,s="";if(window.crypto&&window.crypto.getRandomValues){var a=new Uint32Array(n);'
                . 'window.crypto.getRandomValues(a);for(var i=0;i<n;i++)s+=c.charAt(a[i]%c.length);}'
                . 'else{for(var j=0;j<n;j++)s+=c.charAt(Math.floor(Math.random()*c.length));}'
                . 'var inp=b.parentNode.parentNode.querySelector(\'input[name="custom_token"]\');if(inp)inp.value=s;});'
                . '}());</script>';

            $this->fields_form['input'][] = [
                'type'  => 'free',
                'label' => $this->l('Affiliate code'),
                'name'  => 'token_input',
            ];
            $this->fields_value['token_input'] = $tokenInputHtml;
        }

        $helper = new HelperForm();
        $helper->module          = $this->module;
        $helper->name_controller = $this->controller_name;
        $helper->identifier      = $this->identifier;
        $helper->token           = $this->token;
        $helper->currentIndex    = self::$currentIndex
            . ($id > 0 ? '&id_ks_affiliation_link=' . $id . '&update' . $this->table : '');
        $helper->table           = $this->table;
        $helper->title           = $this->l('Affiliate link');
        $helper->show_toolbar    = true;
        $helper->toolbar_scroll  = false;
        $helper->submit_action   = 'submitAdd' . $this->table;
        $helper->default_form_language    = (int) Configuration::get('PS_LANG_DEFAULT');
        $helper->allow_employee_form_lang = (int) Configuration::get('PS_LANG_DEFAULT');
        $helper->fields_value             = $this->fields_value;
        $helper->tpl_vars                 = [
            'token_value' => !empty($row['token']) ? $row['token'] : '',
        ];

        return $helper->generateForm([['form' => $this->fields_form]]);
    }

    public function postProcess()
    {
        if (Tools::getValue('action') === 'toggleactive') {
            if (Tools::getValue('token') !== $this->token) {
                $this->errors[] = $this->l('Invalid security token.');

                return false;
            }
            $this->processToggleActive((int) Tools::getValue('id_ks_affiliation_link'));

            return true;
        }

        if (Tools::getValue('action') === 'togglefinished') {
            if (Tools::getValue('token') !== $this->token) {
                $this->errors[] = $this->l('Invalid security token.');

                return false;
            }
            $this->processToggleFinished(
                (int) Tools::getValue('id_ks_affiliation_order'),
                (int) Tools::getValue('id_ks_affiliation_link')
            );

            return true;
        }

        if (Tools::isSubmit('delete' . $this->table)) {
            if (Tools::getValue('token') !== $this->token) {
                $this->errors[] = $this->l('Invalid security token.');

                return false;
            }
            $this->processSoftDelete((int) Tools::getValue('id_ks_affiliation_link'));

            return true;
        }

        return parent::postProcess();
    }

    public function processAdd()
    {
        $validated = $this->validateLinkInput();
        if ($validated === null) {
            return false;
        }

        if (Shop::isFeatureActive() && Shop::getContext() !== Shop::CONTEXT_SHOP) {
            $this->errors[] = $this->l('Please select a single shop in the multi-store header before creating an affiliate link.');

            return false;
        }

        $id_shop = (int) Shop::getContextShopID();
        if ($id_shop <= 0) {
            $id_shop = (int) Configuration::get('PS_SHOP_DEFAULT');
        }

        $customToken = trim((string) Tools::getValue('custom_token', ''));

        if ($customToken !== '') {
            if (!preg_match(Ks_affiliation::TOKEN_REGEX, $customToken)) {
                $this->errors[] = $this->l('Affiliate code must contain only letters and digits and be 3–64 characters long.');

                return false;
            }

            $exists = (int) Db::getInstance()->getValue(
                'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'ks_affiliation_link`
                 WHERE `token` = \'' . pSQL($customToken) . '\''
            );

            if ($exists > 0) {
                $this->errors[] = $this->l('This affiliate code is already in use. Please choose another.');

                return false;
            }

            $newToken = $customToken;
        } else {
            try {
                /** @var Ks_affiliation $module */
                $module   = $this->module;
                $newToken = $module->generateToken();
            } catch (\Throwable $e) {
                $this->errors[] = $this->l('Could not generate a unique token. Please retry.');

                return false;
            }
        }

        $now = date('Y-m-d H:i:s');

        $ok = Db::getInstance()->insert($this->table, [
            'token'                => pSQL($newToken),
            'id_shop'              => $id_shop,
            'description'          => pSQL($validated['description']),
            'cookie_lifetime_days' => $validated['lifetime'],
            'payout_percentage'    => (float) $validated['payout'],
            'active'               => $validated['active'],
            'deleted'              => 0,
            'date_add'             => $now,
            'date_upd'             => $now,
        ]);

        if (!$ok) {
            $this->errors[] = $this->l('Could not create the affiliate link.');

            return false;
        }

        $this->confirmations[] = $this->l('Affiliate link created.');
        Tools::redirectAdmin(self::$currentIndex . '&conf=3&token=' . $this->token);

        return true;
    }

    public function processUpdate()
    {
        $id = (int) Tools::getValue($this->identifier);
        if ($id <= 0) {
            $this->errors[] = $this->l('Missing affiliate link ID.');

            return false;
        }

        $exists = (int) Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'ks_affiliation_link`
             WHERE `id_ks_affiliation_link` = ' . $id . ' AND `deleted` = 0'
        );

        if ($exists === 0) {
            $this->errors[] = $this->l('Affiliate link not found.');

            return false;
        }

        $validated = $this->validateLinkInput();
        if ($validated === null) {
            return false;
        }

        $ok = Db::getInstance()->update(
            bqSQL($this->table),
            [
                'description'          => pSQL($validated['description']),
                'cookie_lifetime_days' => $validated['lifetime'],
                'payout_percentage'    => (float) $validated['payout'],
                'active'               => $validated['active'],
                'date_upd'             => date('Y-m-d H:i:s'),
            ],
            '`id_ks_affiliation_link` = ' . $id
        );

        if (!$ok) {
            $this->errors[] = $this->l('Could not save the affiliate link.');

            return false;
        }

        $this->confirmations[] = $this->l('Affiliate link updated.');
        Tools::redirectAdmin(self::$currentIndex . '&conf=4&token=' . $this->token);

        return true;
    }

    private function validateLinkInput(): ?array
    {
        $description = trim((string) Tools::getValue('description'));
        $active      = (int) Tools::getValue('active') === 1 ? 1 : 0;
        $lifetimeRaw = trim((string) Tools::getValue('cookie_lifetime_days'));

        if ($description === '') {
            $this->errors[] = $this->l('Description is required.');

            return null;
        }

        if (mb_strlen($description) > 255) {
            $this->errors[] = $this->l('Description must be 255 characters or fewer.');

            return null;
        }

        if ($lifetimeRaw === '' || !ctype_digit($lifetimeRaw)) {
            $this->errors[] = $this->l('Cookie lifespan is required and must be a positive integer.');

            return null;
        }

        $lifetime = (int) $lifetimeRaw;
        if ($lifetime <= 0 || $lifetime > 3650) {
            $this->errors[] = $this->l('Cookie lifespan must be between 1 and 3650 days.');

            return null;
        }

        $payoutRaw = str_replace(',', '.', trim((string) Tools::getValue('payout_percentage')));
        if ($payoutRaw === '') {
            $payout = 0.0;
        } else {
            if (!is_numeric($payoutRaw)) {
                $this->errors[] = $this->l('Payout percentage must be numeric.');

                return null;
            }

            $payout = round((float) $payoutRaw, 2);
            if ($payout < 0 || $payout > 100) {
                $this->errors[] = $this->l('Payout percentage must be between 0 and 100.');

                return null;
            }
        }

        return [
            'description' => $description,
            'lifetime'    => $lifetime,
            'payout'      => $payout,
            'active'      => $active,
        ];
    }

    private function processSoftDelete(int $id): void
    {
        if ($id <= 0) {
            $this->errors[] = $this->l('Invalid link ID.');

            return;
        }

        $ok = Db::getInstance()->update(
            bqSQL($this->table),
            [
                'deleted'  => 1,
                'active'   => 0,
                'date_upd' => date('Y-m-d H:i:s'),
            ],
            '`id_ks_affiliation_link` = ' . $id
        );

        if (!$ok) {
            $this->errors[] = $this->l('Could not delete the affiliate link.');

            return;
        }

        $this->confirmations[] = $this->l('Affiliate link deleted.');
        Tools::redirectAdmin(self::$currentIndex . '&conf=1&token=' . $this->token);
    }

    private function processToggleActive(int $id): void
    {
        if ($id <= 0) {
            $this->errors[] = $this->l('Invalid link ID.');

            return;
        }

        Db::getInstance()->execute(
            'UPDATE `' . _DB_PREFIX_ . 'ks_affiliation_link`
             SET `active` = 1 - `active`, `date_upd` = \'' . pSQL(date('Y-m-d H:i:s')) . '\'
             WHERE `id_ks_affiliation_link` = ' . $id . ' AND `deleted` = 0'
        );

        Tools::redirectAdmin(self::$currentIndex . '&conf=5&token=' . $this->token);
    }

    private function processToggleFinished(int $id_order_row, int $id_link): void
    {
        if ($id_order_row <= 0 || $id_link <= 0) {
            $this->errors[] = $this->l('Invalid order row.');

            return;
        }

        $shopIds    = Shop::getContextListShopID();
        $shopFilter = '';
        if (!empty($shopIds)) {
            $shopFilter = ' AND `id_shop` IN (' . implode(',', array_map('intval', (array) $shopIds)) . ')';
        }

        Db::getInstance()->execute(
            'UPDATE `' . _DB_PREFIX_ . 'ks_affiliation_order`
             SET `finished` = 1 - `finished`
             WHERE `id_ks_affiliation_order` = ' . $id_order_row . '
               AND `id_ks_affiliation_link` = ' . $id_link
            . $shopFilter
        );

        Tools::redirectAdmin(
            self::$currentIndex
            . '&action=vieworders&id_ks_affiliation_link=' . $id_link
            . '&token=' . $this->token
        );
    }

    /**
     * Per-line returned quantity = max(sum of order_return_detail.product_quantity,
     * order_detail.product_quantity_refunded), clamped to product_quantity.
     *
     * Returns ['status' => 'none'|'partial'|'full',
     *          'effective_amount' => float (kept value),
     *          'returned_amount' => float]
     */
    private function computeReturnSummary(int $id_order, float $fallbackAmount): array
    {
        $rows = Db::getInstance()->executeS(
            'SELECT od.`id_order_detail`,
                    od.`product_quantity`,
                    od.`product_quantity_refunded`,
                    od.`unit_price_tax_incl`,
                    COALESCE((
                        SELECT SUM(ord.`product_quantity`)
                        FROM `' . _DB_PREFIX_ . 'order_return` orr
                        INNER JOIN `' . _DB_PREFIX_ . 'order_return_detail` ord
                                ON ord.`id_order_return` = orr.`id_order_return`
                        WHERE orr.`id_order` = od.`id_order`
                          AND ord.`id_order_detail` = od.`id_order_detail`
                    ), 0) AS `returned_qty`
             FROM `' . _DB_PREFIX_ . 'order_detail` od
             WHERE od.`id_order` = ' . $id_order
        );

        if (!is_array($rows) || empty($rows)) {
            return ['status' => 'none', 'effective_amount' => $fallbackAmount, 'returned_amount' => 0.0];
        }

        $totalQty       = 0;
        $returnedQtySum = 0;
        $returnedValue  = 0.0;

        foreach ($rows as $r) {
            $qty       = (int) $r['product_quantity'];
            $refunded  = (int) $r['product_quantity_refunded'];
            $requested = (int) $r['returned_qty'];
            $price     = (float) $r['unit_price_tax_incl'];

            $effReturned = max($refunded, $requested);
            if ($effReturned > $qty) {
                $effReturned = $qty;
            }

            $totalQty       += $qty;
            $returnedQtySum += $effReturned;
            $returnedValue  += $effReturned * $price;
        }

        if ($returnedQtySum <= 0) {
            $status = 'none';
        } elseif ($returnedQtySum >= $totalQty) {
            $status = 'full';
        } else {
            $status = 'partial';
        }

        $effective = $fallbackAmount - $returnedValue;
        if ($effective < 0) {
            $effective = 0.0;
        }

        return [
            'status'           => $status,
            'effective_amount' => $effective,
            'returned_amount'  => $returnedValue,
        ];
    }

    private function computeOrderStatus(int $id_order, int $completedStateId, int $returnDays, int $delayDays, string $returnStatus): array
    {
        if ($returnStatus === 'full') {
            return [
                'key'   => 'returned',
                'label' => $this->l('Returned'),
                'color' => '#d9534f',
            ];
        }

        if ($returnStatus === 'partial') {
            return [
                'key'   => 'partial',
                'label' => $this->l('Partially Completed'),
                'color' => '#f0ad4e',
            ];
        }

        if ($completedStateId > 0) {
            $completedDate = Db::getInstance()->getValue(
                'SELECT MIN(`date_add`) FROM `' . _DB_PREFIX_ . 'order_history`
                 WHERE `id_order` = ' . $id_order . '
                   AND `id_order_state` = ' . $completedStateId
            );

            if (!empty($completedDate)) {
                $daysSince = (int) floor((time() - strtotime((string) $completedDate)) / 86400);
                $threshold = $returnDays + max(0, $delayDays);
                if ($threshold > 0 && $daysSince >= $threshold) {
                    return [
                        'key'   => 'completed',
                        'label' => $this->l('Completed'),
                        'color' => '#5cb85c',
                    ];
                }
            }
        }

        return [
            'key'   => 'awaiting',
            'label' => $this->l('Awaiting'),
            'color' => '#999999',
        ];
    }

    private function renderOrdersView(int $id_link): string
    {
        $shopIds      = Shop::getContextListShopID();
        $shopFilter   = '';
        if (!empty($shopIds)) {
            $shopFilter = ' AND `id_shop` IN (' . implode(',', array_map('intval', (array) $shopIds)) . ')';
        }

        $link = Db::getInstance()->getRow(
            'SELECT `description`, `payout_percentage`
             FROM `' . _DB_PREFIX_ . 'ks_affiliation_link`
             WHERE `id_ks_affiliation_link` = ' . $id_link
            . $shopFilter
        );

        if (!is_array($link)) {
            return '<p class="alert alert-warning">'
                . htmlspecialchars($this->l('This affiliate link does not belong to the selected shop.'), ENT_QUOTES, 'UTF-8')
                . '</p>';
        }

        $description = isset($link['description']) ? (string) $link['description'] : '';
        $payoutPct   = isset($link['payout_percentage']) ? (float) $link['payout_percentage'] : 0.0;

        $rows = Db::getInstance()->executeS(
            'SELECT kao.`id_ks_affiliation_order`, kao.`finished`,
                    o.`id_order`, o.`reference`, o.`total_paid`, o.`total_products_wt`, o.`date_add`
             FROM `' . _DB_PREFIX_ . 'ks_affiliation_order` kao
             INNER JOIN `' . _DB_PREFIX_ . 'orders` o ON o.`id_order` = kao.`id_order`
             WHERE kao.`id_ks_affiliation_link` = ' . $id_link . '
             ORDER BY o.`date_add` DESC'
        );

        $completedStateId = (int) Configuration::get('KS_AFFILIATION_COMPLETED_STATE');
        $returnDays       = (int) Configuration::get('PS_ORDER_RETURN_NB_DAYS');
        $delayDays        = (int) Configuration::get('KS_AFFILIATION_COMPLETED_DELAY');

        $orders             = [];
        $totalOrdersAmount  = 0.0;
        $totalCompletedAmount = 0.0;
        $totalReturnsAmount = 0.0;
        foreach ((array) $rows as $r) {
            $id_order = (int) $r['id_order'];
            $summary  = $this->computeReturnSummary($id_order, (float) $r['total_products_wt']);
            $status   = $this->computeOrderStatus($id_order, $completedStateId, $returnDays, $delayDays, $summary['status']);

            $totalOrdersAmount  += (float) $r['total_products_wt'];
            $totalReturnsAmount += (float) $summary['returned_amount'];
            if ($status['key'] === 'completed') {
                $totalCompletedAmount += (float) $summary['effective_amount'];
            }

            $orders[] = [
                'id_ks_affiliation_order' => (int) $r['id_ks_affiliation_order'],
                'id_order'     => $id_order,
                'reference'    => (string) $r['reference'],
                'total_paid'   => Tools::displayPrice($summary['effective_amount']),
                'date_add'     => (string) $r['date_add'],
                'order_url'    => $this->context->link->getAdminLink(
                    'AdminOrders',
                    true,
                    ['route' => 'admin_orders_view', 'orderId' => $id_order]
                ),
                'status_label' => $status['label'],
                'status_color' => $status['color'],
                'finished'     => (int) $r['finished'] === 1,
            ];
        }

        $payoutTotal = round($totalCompletedAmount * ($payoutPct / 100), 2);

        $back_url = self::$currentIndex . '&token=' . $this->token;

        $hasPayout = $payoutPct > 0;

        $this->context->smarty->assign([
            'link_description'      => $description,
            'id_link'               => $id_link,
            'toggle_url'            => self::$currentIndex . '&token=' . $this->token,
            'orders'                => $orders,
            'back_url'              => $back_url,
            'has_payout'            => $hasPayout,
            'payout_percentage'     => $hasPayout
                ? rtrim(rtrim(number_format($payoutPct, 2, '.', ''), '0'), '.') . '%'
                : '',
            'total_completed'       => Tools::displayPrice($totalCompletedAmount),
            'total_orders'          => Tools::displayPrice($totalOrdersAmount),
            'total_returns'         => Tools::displayPrice($totalReturnsAmount),
            'total_payout'          => $hasPayout ? Tools::displayPrice($payoutTotal) : '',
        ]);

        return $this->context->smarty->fetch(
            _PS_MODULE_DIR_ . 'ks_affiliation/views/templates/admin/orders.tpl'
        );
    }
}
