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

require_once __DIR__ . '/classes/KsAffiliationLink.php';

class Ks_affiliation extends Module
{
    public const COOKIE_NAME = 'ks_affiliation_token';
    public const TOKEN_REGEX = '/^[a-f0-9]{12}$/';
    public const QUERY_PARAM = 'affiliate_token';

    public function __construct()
    {
        $this->name                   = 'ks_affiliation';
        $this->tab                    = 'administration';
        $this->version                = '1.0.3';
        $this->author                 = 'KS Development';
        $this->need_instance          = 0;
        $this->bootstrap              = true;
        $this->ps_versions_compliancy = ['min' => '8.0.0', 'max' => '8.9.99'];
        $this->multistore_compatibility = self::MULTISTORE_COMPATIBILITY_YES;

        parent::__construct();

        $this->displayName = $this->l('KS Affiliation');
        $this->description = $this->l('Affiliate link tracking — generate URLs, set a visitor cookie, and attribute orders.');
    }

    public function install(): bool
    {
        return parent::install()
            && $this->installDb()
            && $this->installTab()
            && $this->registerHook('actionValidateOrder')
            && $this->registerHook('actionDispatcher')
            && $this->registerHook('displayHeader')
            && $this->registerHook('displayBackOfficeHeader')
            && Configuration::updateValue('KS_AFFILIATION_COOKIE_LIFETIME', 30)
            && Configuration::updateValue('KS_AFFILIATION_COMPLETED_STATE', 0);
    }

    public function uninstall(): bool
    {
        return parent::uninstall()
            && $this->uninstallTab()
            && $this->uninstallDb()
            && Configuration::deleteByName('KS_AFFILIATION_COOKIE_LIFETIME')
            && Configuration::deleteByName('KS_AFFILIATION_COMPLETED_STATE');
    }

    public function getContent(): string
    {
        $output = '';

        if (Tools::isSubmit('submitKsAffiliationConfig')) {
            if (Tools::getValue('token') !== Tools::getAdminTokenLite('AdminModules')) {
                $output .= $this->displayError($this->l('Invalid security token.'));
            } else {
                $state = (int) Tools::getValue('KS_AFFILIATION_COMPLETED_STATE');
                if ($state <= 0) {
                    $output .= $this->displayError($this->l('Please select an order status.'));
                } else {
                    Configuration::updateValue('KS_AFFILIATION_COMPLETED_STATE', $state);
                    $output .= $this->displayConfirmation($this->l('Settings saved.'));
                }
            }
        }

        return $output . $this->renderConfigForm();
    }

    private function renderConfigForm(): string
    {
        $states  = OrderState::getOrderStates((int) $this->context->language->id);
        $options = [];
        foreach ((array) $states as $state) {
            $options[] = [
                'id_option' => (int) $state['id_order_state'],
                'name'      => (string) $state['name'],
            ];
        }

        $returnDays = (int) Configuration::get('PS_ORDER_RETURN_NB_DAYS');

        $fields_form = [[
            'form' => [
                'legend' => [
                    'title' => $this->l('KS Affiliation settings'),
                    'icon'  => 'icon-cogs',
                ],
                'description' => sprintf(
                    $this->l('Merchandise Returns time limit (from Customer Service): %d days. Orders that have been in the "Order completed" status for at least this many days are shown as "Completed".'),
                    $returnDays
                ),
                'input' => [
                    [
                        'type'     => 'select',
                        'label'    => $this->l('Order completed status'),
                        'name'     => 'KS_AFFILIATION_COMPLETED_STATE',
                        'required' => true,
                        'hint'     => $this->l('Pick the order status that represents a finalized, paid, shipped order.'),
                        'options'  => [
                            'query' => $options,
                            'id'    => 'id_option',
                            'name'  => 'name',
                        ],
                    ],
                ],
                'submit' => ['title' => $this->l('Save')],
            ],
        ]];

        $helper                  = new HelperForm();
        $helper->module          = $this;
        $helper->name_controller = $this->name;
        $helper->token           = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex    = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->default_form_language    = (int) Configuration::get('PS_LANG_DEFAULT');
        $helper->allow_employee_form_lang = (int) Configuration::get('PS_LANG_DEFAULT');
        $helper->title           = $this->displayName;
        $helper->submit_action   = 'submitKsAffiliationConfig';
        $helper->fields_value    = [
            'KS_AFFILIATION_COMPLETED_STATE' => (int) Configuration::get('KS_AFFILIATION_COMPLETED_STATE'),
        ];

        return $helper->generateForm($fields_form);
    }

    private function installDb(): bool
    {
        $sqlLink = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'ks_affiliation_link` (
            `id_ks_affiliation_link` INT(11) NOT NULL AUTO_INCREMENT,
            `token`                  VARCHAR(12) NOT NULL,
            `id_shop`                INT(11) UNSIGNED NOT NULL,
            `description`            VARCHAR(255) NOT NULL DEFAULT \'\',
            `cookie_lifetime_days`   INT(11) UNSIGNED NOT NULL DEFAULT 30,
            `payout_percentage`      DECIMAL(5,2) NOT NULL DEFAULT 0.00,
            `active`                 TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
            `deleted`                TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
            `date_add`               DATETIME NOT NULL,
            `date_upd`               DATETIME NOT NULL,
            PRIMARY KEY (`id_ks_affiliation_link`),
            UNIQUE KEY `uq_token` (`token`),
            KEY `idx_active_deleted` (`active`, `deleted`),
            KEY `idx_shop` (`id_shop`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

        $sqlOrder = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'ks_affiliation_order` (
            `id_ks_affiliation_order` INT(11) NOT NULL AUTO_INCREMENT,
            `id_ks_affiliation_link`  INT(11) NOT NULL,
            `id_order`                INT(11) UNSIGNED NOT NULL,
            `id_shop`                 INT(11) UNSIGNED NOT NULL,
            `date_add`                DATETIME NOT NULL,
            PRIMARY KEY (`id_ks_affiliation_order`),
            UNIQUE KEY `uq_order` (`id_order`),
            KEY `idx_link` (`id_ks_affiliation_link`),
            KEY `idx_shop` (`id_shop`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

        return Db::getInstance()->execute($sqlLink)
            && Db::getInstance()->execute($sqlOrder);
    }

    private function uninstallDb(): bool
    {
        return Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'ks_affiliation_order`')
            && Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'ks_affiliation_link`');
    }

    private function installTab(): bool
    {
        $tab             = new Tab();
        $tab->class_name = 'AdminKsAffiliation';
        $tab->module     = $this->name;
        $tab->id_parent  = (int) Tab::getIdFromClassName('AdminCatalog');
        $tab->icon       = '';

        foreach (Language::getLanguages() as $lang) {
            $tab->name[(int) $lang['id_lang']] = $this->l('Affiliate Links');
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

    /**
     * Generate a unique 12-character hex token. Retries up to 5 times on collision.
     *
     * @throws \RuntimeException When uniqueness cannot be achieved after 5 attempts.
     */
    public function generateToken(): string
    {
        for ($i = 0; $i < 5; $i++) {
            $token  = bin2hex(random_bytes(6));
            $exists = (int) Db::getInstance()->getValue(
                'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'ks_affiliation_link`
                 WHERE `token` = \'' . pSQL($token) . '\''
            );

            if ($exists === 0) {
                return $token;
            }
        }

        throw new \RuntimeException('KsAffiliation: could not generate unique token after 5 attempts');
    }

    public function hookActionValidateOrder(array $params): void
    {
        $token = isset($_COOKIE[self::COOKIE_NAME]) ? (string) $_COOKIE[self::COOKIE_NAME] : '';

        if ($token === '' || !preg_match(self::TOKEN_REGEX, $token)) {
            return;
        }

        if (!isset($params['order']) || !is_object($params['order'])) {
            return;
        }

        $id_order = (int) $params['order']->id;
        $id_shop  = (int) $params['order']->id_shop;

        if ($id_order === 0 || $id_shop === 0) {
            return;
        }

        $id_link = (int) Db::getInstance()->getValue(
            'SELECT `id_ks_affiliation_link` FROM `' . _DB_PREFIX_ . 'ks_affiliation_link`
             WHERE `token` = \'' . pSQL($token) . '\'
               AND `id_shop` = ' . $id_shop . '
               AND `active` = 1
               AND `deleted` = 0'
        );

        if ($id_link === 0) {
            return;
        }

        try {
            Db::getInstance()->insert('ks_affiliation_order', [
                'id_ks_affiliation_link' => $id_link,
                'id_order'               => $id_order,
                'id_shop'                => $id_shop,
                'date_add'               => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            // Silently ignore duplicate-key errors — order already tracked.
        }
    }

    public function hookActionDispatcher(array $params): void
    {
        if (isset($params['controller_type'])
            && (int) $params['controller_type'] !== Dispatcher::FC_FRONT
        ) {
            return;
        }

        $raw = Tools::getValue(self::QUERY_PARAM);

        if (!is_string($raw) || $raw === '') {
            return;
        }

        $token = strtolower(preg_replace('/[^a-f0-9]/i', '', $raw));

        if ($token === '' || !preg_match(self::TOKEN_REGEX, $token)) {
            return;
        }

        $id_shop = (int) $this->context->shop->id;

        $row = Db::getInstance()->getRow(
            'SELECT `id_ks_affiliation_link`, `cookie_lifetime_days`
             FROM `' . _DB_PREFIX_ . 'ks_affiliation_link`
             WHERE `token` = \'' . pSQL($token) . '\'
               AND `id_shop` = ' . $id_shop . '
               AND `active` = 1
               AND `deleted` = 0'
        );

        if (!is_array($row) || (int) $row['id_ks_affiliation_link'] === 0) {
            return;
        }

        $days = (int) $row['cookie_lifetime_days'];
        if ($days <= 0) {
            $days = (int) Configuration::get('KS_AFFILIATION_COOKIE_LIFETIME');
        }
        if ($days <= 0) {
            $days = 30;
        }

        setcookie(
            self::COOKIE_NAME,
            $token,
            [
                'expires'  => time() + ($days * 86400),
                'path'     => '/',
                'secure'   => Tools::usingSecureMode(),
                'httponly' => true,
                'samesite' => 'Lax',
            ]
        );

        $this->redirectStrippingToken();
    }

    private function redirectStrippingToken(): void
    {
        if (!isset($_SERVER['REQUEST_URI']) || headers_sent()) {
            return;
        }

        $uri   = (string) preg_replace('/[\r\n]+/', '', (string) $_SERVER['REQUEST_URI']);
        $parts = explode('?', $uri, 2);
        $path  = $parts[0];

        if (!isset($parts[1]) || $parts[1] === '') {
            return;
        }

        parse_str($parts[1], $query);
        unset($query[self::QUERY_PARAM]);

        $rebuilt = $path;
        if (!empty($query)) {
            $rebuilt .= '?' . http_build_query($query);
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Location: ' . $rebuilt, true, 302);
        exit;
    }

    public function hookDisplayHeader(): string
    {
        if (!array_key_exists(self::QUERY_PARAM, $_GET)) {
            return '';
        }

        return '<script>(function(){try{var u=new URL(window.location.href);'
            . 'if(u.searchParams.has(' . json_encode(self::QUERY_PARAM) . ')){'
            . 'u.searchParams.delete(' . json_encode(self::QUERY_PARAM) . ');'
            . 'window.history.replaceState({},document.title,u.pathname+(u.search?u.search:"")+u.hash);'
            . '}}catch(e){}}());</script>';
    }

    public function hookDisplayBackOfficeHeader(): string
    {
        if (!isset($this->context->controller)
            || $this->context->controller->controller_name !== 'AdminKsAffiliation'
        ) {
            return '';
        }

        $this->context->controller->addCSS($this->_path . 'views/css/admin.css');
        $this->context->controller->addJS($this->_path . 'views/js/admin.js');

        return '';
    }
}
