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

namespace KsAffiliation\Tests\Unit;

use PHPUnit\Framework\TestCase;

class KsAffiliationTokenTest extends TestCase
{
    public function testTokenFormatMatches12HexChars(): void
    {
        $token = bin2hex(random_bytes(6));

        $this->assertSame(12, strlen($token));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{12}$/', $token);
    }

    public function testTokensAreNotTriviallyRepeated(): void
    {
        $seen = [];
        for ($i = 0; $i < 50; $i++) {
            $token = bin2hex(random_bytes(6));
            $this->assertArrayNotHasKey($token, $seen);
            $seen[$token] = true;
        }
    }
}
