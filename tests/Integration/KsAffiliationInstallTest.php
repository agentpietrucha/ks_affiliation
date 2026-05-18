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

namespace KsAffiliation\Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Integration test stub. Runs inside a bootstrapped PrestaShop environment.
 * Verifies the module installs cleanly, creates its tables and tab, and uninstalls without leaving artifacts.
 */
class KsAffiliationInstallTest extends TestCase
{
    public function testModuleInstallCreatesTables(): void
    {
        if (!class_exists('\Module') || !defined('_PS_VERSION_')) {
            $this->markTestSkipped('PrestaShop environment not bootstrapped.');
        }

        $module = \Module::getInstanceByName('ks_affiliation');
        $this->assertNotFalse($module, 'Module instance could not be loaded.');

        if (!\Module::isInstalled('ks_affiliation')) {
            $this->assertTrue($module->install(), 'Module install() returned false.');
        }

        $tables = \Db::getInstance()->executeS(
            'SHOW TABLES LIKE \'' . pSQL(_DB_PREFIX_ . 'ks_affiliation_link') . '\''
        );
        $this->assertNotEmpty($tables, 'ks_affiliation_link table missing after install.');

        $this->assertGreaterThan(
            0,
            (int) \Tab::getIdFromClassName('AdminKsAffiliation'),
            'AdminKsAffiliation tab missing after install.'
        );
    }

    public function testModuleUninstallRemovesTables(): void
    {
        if (!class_exists('\Module') || !defined('_PS_VERSION_')) {
            $this->markTestSkipped('PrestaShop environment not bootstrapped.');
        }

        $module = \Module::getInstanceByName('ks_affiliation');
        if (\Module::isInstalled('ks_affiliation')) {
            $this->assertTrue($module->uninstall(), 'Module uninstall() returned false.');
        }

        $tables = \Db::getInstance()->executeS(
            'SHOW TABLES LIKE \'' . pSQL(_DB_PREFIX_ . 'ks_affiliation_link') . '\''
        );
        $this->assertEmpty($tables, 'ks_affiliation_link table still present after uninstall.');
    }
}
