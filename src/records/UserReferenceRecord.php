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
 *
 * @property string $id
 * @property string $uid
 * @property string $userId
 * @property string $dateCreated
 * @property string $user
 * @property string $email
 * @property string $username
 * @property string $password
 * @property string $firstName
 * @property string $lastName
 * @property string $reference
 */
class UserReferenceRecord extends ActiveRecord
{
    // Public Static Methods
    // =========================================================================

    const TABLE_NAME = '{{%lightweightaccess_users}}';

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return static::TABLE_NAME;
    }

    public function rules()
    {
        return [
            [['email', 'username', 'firstName', 'lastName', 'reference'], 'string'],
            [['email', 'username', 'reference'], 'required'],
        ];
    }
}
