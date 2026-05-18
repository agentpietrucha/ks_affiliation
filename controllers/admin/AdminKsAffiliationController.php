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

        $this->_select = "a.`token` AS `full_url`,
                          (SELECT COUNT(*) FROM `" . _DB_PREFIX_ . "ks_affiliation_order` kao
                          WHERE kao.`id_ks_affiliation_link` = a.`id_ks_affiliation_link`) AS `orders_count`";

        $this->fields_list = [
            'id_ks_affiliation_link' => [
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ],
            'description' => [
                'title' => $this->l('Description'),
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
        $base = $this->context->link->getPageLink('index', true);
        $sep  = (strpos($base, '?') === false) ? '?' : '&';
        $url  = $base . $sep . Ks_affiliation::QUERY_PARAM . '=' . rawurlencode($row['token']);

        $rendered = '<div class="input-group">';
        $rendered .= '<input type="text" class="form-control" readonly value="'
            . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">';
        $rendered .= '<span class="input-group-btn">';
        $rendered .= '<button type="button" class="btn btn-default ks-copy-url" data-url="'
            . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">'
            . htmlspecialchars($this->l('Copy'), ENT_QUOTES, 'UTF-8')
            . '</button>';
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
        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Affiliate link'),
                'icon'  => 'icon-link',
            ],
            'input' => [
                [
                    'type'     => 'text',
                    'label'    => $this->l('Description'),
                    'name'     => 'description',
                    'required' => true,
                    'maxlength' => 255,
                    'hint'     => $this->l('Internal label to identify this link.'),
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

        $id        = (int) Tools::getValue('id_ks_affiliation_link');
        $row       = [];

        if ($id > 0) {
            $row = Db::getInstance()->getRow(
                'SELECT * FROM `' . _DB_PREFIX_ . 'ks_affiliation_link`
                 WHERE `id_ks_affiliation_link` = ' . $id
            ) ?: [];
        }

        $this->fields_value = [
            'description'   => $row['description'] ?? '',
            'active'        => isset($row['active']) ? (int) $row['active'] : 1,
            'token_display' => $row['token'] ?? '',
        ];

        if (!empty($row['token'])) {
            $this->fields_form['input'][] = [
                'type'     => 'free',
                'label'    => $this->l('Token'),
                'name'     => 'token_display',
                'desc'     => $row['token'],
            ];
        }

        $helper = new HelperForm();
        $helper->module          = $this->module;
        $helper->name_controller = $this->controller_name;
        $helper->identifier      = $this->identifier;
        $helper->token           = $this->token;
        $helper->currentIndex    = self::$currentIndex;
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

    public function postProcess(): void
    {
        $token = Tools::getValue('token');
        if (Tools::isSubmit('submitAdd' . $this->table) || Tools::isSubmit('delete' . $this->table)) {
            if ($token !== $this->token) {
                $this->errors[] = $this->l('Invalid security token.');

                return;
            }
        }

        if (Tools::isSubmit('submitAdd' . $this->table)) {
            $this->processSaveLink();

            return;
        }

        if (Tools::isSubmit('delete' . $this->table)) {
            $this->processSoftDelete((int) Tools::getValue('id_ks_affiliation_link'));

            return;
        }

        if (Tools::getValue('action') === 'toggleactive') {
            if ($token !== $this->token) {
                $this->errors[] = $this->l('Invalid security token.');

                return;
            }
            $this->processToggleActive((int) Tools::getValue('id_ks_affiliation_link'));

            return;
        }

        parent::postProcess();
    }

    private function processSaveLink(): void
    {
        $id          = (int) Tools::getValue('id_ks_affiliation_link');
        $description = trim((string) Tools::getValue('description'));
        $active      = (int) Tools::getValue('active') === 1 ? 1 : 0;

        if ($description === '') {
            $this->errors[] = $this->l('Description is required.');

            return;
        }

        if (mb_strlen($description) > 255) {
            $this->errors[] = $this->l('Description must be 255 characters or fewer.');

            return;
        }

        $now = date('Y-m-d H:i:s');

        if ($id > 0) {
            $ok = Db::getInstance()->update(
                bqSQL($this->table),
                [
                    'description' => pSQL($description),
                    'active'      => $active,
                    'date_upd'    => $now,
                ],
                '`id_ks_affiliation_link` = ' . $id
            );

            if (!$ok) {
                $this->errors[] = $this->l('Could not save the affiliate link.');

                return;
            }

            $this->confirmations[] = $this->l('Affiliate link updated.');
            Tools::redirectAdmin(self::$currentIndex . '&conf=4&token=' . $this->token);
        }

        try {
            /** @var Ks_affiliation $module */
            $module     = $this->module;
            $newToken   = $module->generateToken();
        } catch (\Throwable $e) {
            $this->errors[] = $this->l('Could not generate a unique token. Please retry.');

            return;
        }

        $ok = Db::getInstance()->insert($this->table, [
            'token'       => pSQL($newToken),
            'description' => pSQL($description),
            'active'      => $active,
            'deleted'     => 0,
            'date_add'    => $now,
            'date_upd'    => $now,
        ]);

        if (!$ok) {
            $this->errors[] = $this->l('Could not create the affiliate link.');

            return;
        }

        $this->confirmations[] = $this->l('Affiliate link created.');
        Tools::redirectAdmin(self::$currentIndex . '&conf=3&token=' . $this->token);
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

    private function renderOrdersView(int $id_link): string
    {
        $description = (string) Db::getInstance()->getValue(
            'SELECT `description` FROM `' . _DB_PREFIX_ . 'ks_affiliation_link`
             WHERE `id_ks_affiliation_link` = ' . $id_link
        );

        $rows = Db::getInstance()->executeS(
            'SELECT o.`id_order`, o.`reference`, o.`total_paid`, o.`date_add`
             FROM `' . _DB_PREFIX_ . 'ks_affiliation_order` kao
             INNER JOIN `' . _DB_PREFIX_ . 'orders` o ON o.`id_order` = kao.`id_order`
             WHERE kao.`id_ks_affiliation_link` = ' . $id_link . '
             ORDER BY o.`date_add` DESC'
        );

        $orders = [];
        foreach ((array) $rows as $r) {
            $orders[] = [
                'id_order'   => (int) $r['id_order'],
                'reference'  => (string) $r['reference'],
                'total_paid' => Tools::displayPrice((float) $r['total_paid']),
                'date_add'   => (string) $r['date_add'],
                'order_url'  => $this->context->link->getAdminLink('AdminOrders')
                    . '&id_order=' . (int) $r['id_order'] . '&vieworder',
            ];
        }

        $back_url = self::$currentIndex . '&token=' . $this->token;

        $this->context->smarty->assign([
            'link_description' => $description,
            'orders'           => $orders,
            'back_url'         => $back_url,
        ]);

        return $this->context->smarty->fetch(
            _PS_MODULE_DIR_ . 'ks_affiliation/views/templates/admin/orders.tpl'
        );
    }
}
