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

function upgrade_module_1_0_2(Module $module): bool
{
    $hasColumn = Db::getInstance()->getValue(
        'SHOW COLUMNS FROM `' . _DB_PREFIX_ . 'ks_affiliation_link` LIKE \'payout_percentage\''
    );

    if ($hasColumn) {
        return true;
    }

    return (bool) Db::getInstance()->execute(
        'ALTER TABLE `' . _DB_PREFIX_ . 'ks_affiliation_link`
         ADD COLUMN `payout_percentage` DECIMAL(5,2) NOT NULL DEFAULT 0.00
         AFTER `cookie_lifetime_days`'
    );
}
