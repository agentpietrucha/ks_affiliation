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

function upgrade_module_1_0_4(Module $module): bool
{
    $hasColumn = Db::getInstance()->getValue(
        'SHOW COLUMNS FROM `' . _DB_PREFIX_ . 'ks_affiliation_order` LIKE \'finished\''
    );

    if ($hasColumn) {
        return true;
    }

    return (bool) Db::getInstance()->execute(
        'ALTER TABLE `' . _DB_PREFIX_ . 'ks_affiliation_order`
         ADD COLUMN `finished` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0
         AFTER `id_shop`'
    );
}
