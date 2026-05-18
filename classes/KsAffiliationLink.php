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

class KsAffiliationLink extends ObjectModel
{
    /** @var int */
    public $id_ks_affiliation_link;

    /** @var string */
    public $token;

    /** @var string */
    public $description;

    /** @var int */
    public $cookie_lifetime_days = 30;

    /** @var float */
    public $payout_percentage = 0.0;

    /** @var int */
    public $active = 1;

    /** @var int */
    public $deleted = 0;

    /** @var string */
    public $date_add;

    /** @var string */
    public $date_upd;

    public static $definition = [
        'table'   => 'ks_affiliation_link',
        'primary' => 'id_ks_affiliation_link',
        'fields'  => [
            'token'       => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'size' => 12, 'required' => true],
            'description'          => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 255],
            'cookie_lifetime_days' => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedInt', 'required' => true],
            'payout_percentage'    => ['type' => self::TYPE_FLOAT,  'validate' => 'isFloat',       'required' => true],
            'active'               => ['type' => self::TYPE_BOOL,   'validate' => 'isBool'],
            'deleted'     => ['type' => self::TYPE_BOOL,   'validate' => 'isBool'],
            'date_add'    => ['type' => self::TYPE_DATE,   'validate' => 'isDate'],
            'date_upd'    => ['type' => self::TYPE_DATE,   'validate' => 'isDate'],
        ],
    ];
}
