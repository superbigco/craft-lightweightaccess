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

use craft\elements\User;
use craft\base\Model;

/**
 * @author    Superbig
 * @package   LightweightAccess
 * @since     1.0.0
 */
class UserReferenceModel extends Model
{
    // Public Properties
    // =========================================================================

    public $id;
    public $uid;
    public $userId;
    public $dateCreated;
    public $user;
    public $email;
    public $username;
    public $password;
    public $firstName;
    public $lastName;
    public $reference;

    // Public Methods
    // =========================================================================

    public function getUserData(): array
    {
        return [
            'email'     => $this->email,
            'username'  => $this->username,
            'firstName' => $this->firstName,
            'lastName'  => $this->lastName,
        ];
    }

    public function getUsername(): ?string
    {
        return $this->email;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['email', 'username', 'password', 'firstName', 'lastName', 'reference'], 'string'],
            [['email', 'username', 'password', 'reference'], 'required'],
        ];
    }
}
