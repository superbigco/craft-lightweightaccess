<?php
/**
 * Lightweight Access plugin for Craft CMS 3.x
 *
 * LDAP SSO for Craft
 *
 * @link      https://superbig.co
 * @copyright Copyright (c) 2018 Superbig
 */

namespace superbig\lightweightaccess\records;

use superbig\lightweightaccess\LightweightAccess;

use Craft;
use craft\db\ActiveRecord;

/**
 * @author    Superbig
 * @package   LightweightAccess
 * @since     1.0.0
 */
class LogRecord extends ActiveRecord
{
    // Public Static Methods
    // =========================================================================

    const TABLE_NAME = '{{%lightweightaccess_logs}}';

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return static::TABLE_NAME;
    }
}
