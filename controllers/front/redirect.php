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

class Ks_affiliationRedirectModuleFrontController extends ModuleFrontController
{
    public function initContent(): void
    {
        $homepage = $this->context->link->getPageLink('index', true);
        $token    = (string) Tools::getValue('token');

        if ($token === '' || !preg_match(Ks_affiliation::TOKEN_REGEX, $token)) {
            Tools::redirect($homepage);

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
            Tools::redirect($homepage);

            return;
        }

        $this->setAffiliateCookie($token, (int) $row['cookie_lifetime_days']);

        Tools::redirect($homepage);
    }

    private function setAffiliateCookie(string $token, int $days): void
    {
        if ($days <= 0) {
            $days = (int) Configuration::get('KS_AFFILIATION_COOKIE_LIFETIME');
        }
        if ($days <= 0) {
            $days = 30;
        }
        $lifetime = time() + ($days * 86400);

        setcookie(
            Ks_affiliation::COOKIE_NAME,
            $token,
            [
                'expires'  => $lifetime,
                'path'     => '/',
                'secure'   => Tools::usingSecureMode(),
                'httponly' => true,
                'samesite' => 'Lax',
            ]
        );
    }
}
