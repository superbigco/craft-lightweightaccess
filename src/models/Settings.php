<?php
/**
 * Lightweight Access plugin for Craft CMS 3.x
 *
 * LDAP SSO for Craft
 *
 * @link      https://superbig.co
 * @copyright Copyright (c) 2018 Superbig
 */

namespace superbig\lightweightaccess\models;

use superbig\lightweightaccess\LightweightAccess;

use Craft;
use craft\base\Model;

/**
 * @author    Superbig
 * @package   LightweightAccess
 * @since     1.0.0
 *
 * @property array  $hosts
 * @property array  $userAttributesToSearch
 * @property string $firstNameAttribute
 * @property string $lastNameAttribute
 * @property string $emailAttribute
 * @property string $baseDN
 * @property string $groupHandle
 * @property string $username
 * @property string $password
 * @property int    $port
 * @property string $accountSuffix
 * @property string $accountPrefix
 * @property string $adminAccountPrefix
 * @property string $adminAccountSuffix
 * @property bool   $ssl
 * @property bool   $tls
 * @property bool   $referrals
 */
class Settings extends Model
{
    // Public Properties
    // =========================================================================

    public $hosts                  = [];
    public $userAttributesToSearch = [];
    public $firstNameAttribute     = 'givenName';
    public $lastNameAttribute      = 'sn';
    public $emailAttribute         = 'mail';
    public $baseDN;
    public $groupHandle;
    public $username;
    public $password;
    public $port;
    public $accountSuffix;
    public $accountPrefix;
    public $adminAccountPrefix;
    public $adminAccountSuffix;
    public $ssl                    = false;
    public $tls                    = false; //['tls'
    public $referrals              = false;
    public $defaultUserGroup;

    // Public Methods
    // =========================================================================

    public function getClientConfig(): array
    {
        $config = [
            'hosts'                => $this->hosts,
            'base_dn'              => $this->baseDN,
            'username'             => $this->username,
            'password'             => $this->password,
            'account_suffix'       => $this->accountSuffix,
            'account_prefix'       => $this->accountPrefix,
            'admin_account_prefix' => $this->adminAccountPrefix,
            'admin_account_suffix' => $this->adminAccountSuffix,
            'use_ssl'              => $this->ssl,
            'use_tls'              => $this->tls,
            'follow_referrals'     => $this->referrals,

        ];

        if (!empty($this->port)) {
            $config['port'] = (int)$this->port;
        }

        return array_filter($config);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            //['someAttribute', 'string'],
            ['firstNameAttribute', 'default', 'value' => 'givenName'],
            ['lastNameAttribute', 'default', 'value' => 'sn'],
        ];
    }
}
