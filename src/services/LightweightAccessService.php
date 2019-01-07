<?php
/**
 * Lightweight Access plugin for Craft CMS 3.x
 *
 * LDAP SSO for Craft
 *
 * @link      https://superbig.co
 * @copyright Copyright (c) 2018 Superbig
 */

namespace superbig\lightweightaccess\services;

use Adldap\Adldap;
use Adldap\Auth\BindException;
use Adldap\Auth\PasswordRequiredException;
use Adldap\Auth\UsernameRequiredException;
use Adldap\Query\Builder;
use Adldap\Query\Factory;
use craft\db\Query;
use craft\elements\User;
use craft\events\AuthenticateUserEvent;
use craft\events\LoginFailureEvent;
use craft\helpers\UrlHelper;
use craft\web\Response;
use superbig\lightweightaccess\LightweightAccess;
use superbig\lightweightaccess\models\UserReferenceModel;
use superbig\lightweightaccess\records\UserReferenceRecord;
use Yii;
use yii\db\Exception;
use yii\web\BadRequestHttpException;
use yii\web\Response as YiiResponse;

use Craft;
use craft\base\Component;

/**
 * @author    Superbig
 * @package   LightweightAccess
 * @since     1.0.0
 */
class LightweightAccessService extends Component
{
    public function getReferenceByUserId(int $userId)
    {
        $query = $this
            ->_createQuery()
            ->where('userId = :userId', [':userId' => $userId])
            ->one();

        if (!$query) {
            return null;
        }

        return new UserReferenceModel($query);
    }

    public function getReferenceByReference(string $reference)
    {
        $query = $this
            ->_createQuery()
            ->where('reference = :reference', [':reference' => $reference])
            ->one();

        if (!$query) {
            return null;
        }

        return new UserReferenceModel($query);
    }

    public function getUserByReference(string $reference): ?User
    {
        $userId = $this
            ->_createQuery()
            ->select(['userId'])
            ->where('reference = :reference', [':reference' => $reference])
            ->scalar();

        if (!$userId) {
            return null;
        }

        return Craft::$app->getUsers()->getUserById($userId);
    }

    // Public Methods
    // =========================================================================

    public function onLoginFailure(LoginFailureEvent $event)
    {
        $loginName  = (string)Craft::$app->getRequest()->getRequiredBodyParam('loginName');
        $password   = (string)Craft::$app->getRequest()->getRequiredBodyParam('password');
        $rememberMe = (bool)Craft::$app->getRequest()->getBodyParam('rememberMe');


        if ($user = $this->lookupUsername($loginName, $password)) {
            Craft::$app->getUser()->loginByUserId($user->id);

            return $this->_handleLogin();
        }
    }

    private function _handleLogin()
    {
        $userService = Craft::$app->getUser();
        $returnUrl   = $userService->getReturnUrl();

        // Clear it out
        $userService->removeReturnUrl();

        // If this was an Ajax request, just return success:true
        if (Craft::$app->getRequest()->getAcceptsJson()) {
            $response         = \Yii::$app->getResponse();
            $response->format = Response::FORMAT_JSON;
            $response->data   = [
                'success'   => true,
                'returnUrl' => $returnUrl,
            ];

            return $response->send();
        }

        Craft::$app->getSession()->setNotice(Craft::t('app', 'Logged in.'));

        return $this->redirectToPostedUrl($userService->getIdentity(), $returnUrl);
    }

    /**
     * @param null $username
     * @param null $password
     *
     * @return bool|User
     * @throws \Adldap\Auth\BindException
     * @throws \Adldap\Auth\PasswordRequiredException
     * @throws \Adldap\Auth\UsernameRequiredException
     * @throws \Throwable
     * @throws \craft\errors\ElementNotFoundException
     * @throws \yii\base\Exception
     */
    public function lookupUsername($username = null, $password = null)
    {
        $settings = LightweightAccess::$plugin->getSettings();
        $ldap     = new Adldap();
        //$provider = new \Adldap\Connections\Provider($settings->getClientConfig());
        $ldap->addProvider($settings->getClientConfig());

        try {
            $provider = $ldap->connect('default');
            $provider->auth()->bindAsAdministrator();

            try {
                $lookupByUsernameOrEmail = null;
                //$builder                 = $provider->search()->users();
                $builder = $provider->search();

                /** @var Factory $search */
                $search = array_reduce($settings->userAttributesToSearch, function($builder, $attribute) use ($username) {
                    /** @var Factory $builder */
                    return $builder->orWhereEquals($attribute, $username);
                }, $builder);

                //var_dump($search);
                //die;

                /** @var \Adldap\Models\User $ldapUser */
                $ldapUser = $search->first();

                if (!$ldapUser) {
                    return false;
                }

                // Authenticate with LDAP
                $ldapDn = $ldapUser->getDistinguishedName();

                if ($provider->auth()->attempt($ldapDn, $password)) {
                    //$rec     = $search->find($username);
                    $lookupByUsernameOrEmail = (new Query())
                        ->select('id')
                        ->from('users')
                        ->where(['or', 'username = :username', 'email = :username'], [':username' => $username])
                        ->one();

                    $referenceAttribute = $settings->uniqueAttribute;
                    $ldapUserAttributes = $ldapUser->jsonSerialize();
                    $guid               = $ldapUser->getFirstAttribute($referenceAttribute) ?? $ldapUser->getEmail();

                    // var_dump($referenceAttribute);
                    //var_dump($guid);
                    // var_dump($ldapUser->getConvertedGuid());
                    // var_dump($ldapUserAttributes);
                    // die;

                    $existingUser = $this->getUserByReference($guid);

                    //DOES USER ALREADY EXIST ON LOCAL CRAFT USER TABLE
                    if ($lookupByUsernameOrEmail === null && !$existingUser) { //CREATE USER IN TABLE
                        $email     = $ldapUser->getFirstAttribute($settings->emailAttribute) ?? $ldapUser->getEmail();
                        $firstName = $ldapUser->getFirstName();
                        $lastName  = $ldapUser->getLastName();

                        $userReference = new UserReferenceModel([
                            'email'     => $email,
                            'username'  => $username,
                            'password'  => $password,
                            'firstName' => $firstName,
                            'lastName'  => $lastName,
                            'reference' => $guid,
                        ]);

                        if ($user = $this->createNewUser($userReference)) {
                            return $user;
                        }
                        else {
                            // @todo Log no user created
                            return null;
                        }
                    }
                    else { // User already exists in local db
                        $id                = $lookupByUsernameOrEmail['id'];
                        $user              = $existingUser ?? Craft::$app->getUsers()->getUserById($id);
                        $user->newPassword = $password;

                        // @todo Log error
                        Craft::$app->getElements()->saveElement($user); //save user

                        return $user;
                    }
                }
                else {
                    return false;
                }
            } catch (UsernameRequiredException $e) {
                // The user didn't supply a username.
                //craft()->userSession->setError(Craft::t('User did not provide username.'));

                return false;
                //die("User did not provide a username");
            } catch (PasswordRequiredException $e) {
                // The user didn't supply a password.
                //craft()->userSession->setError(Craft::t('User did not provide password.'));

                return false;
            }
        } catch (BindException $e) {
            //craft()->userSession->setError(Craft::t('Can\'t bind to LDAP server.'));
            return false;
        }
    }

    /**
     * @param UserReferenceModel $userReference
     *
     * @return User|null
     * @throws \Throwable
     * @throws \craft\errors\ElementNotFoundException
     * @throws \yii\base\Exception
     */
    public function createNewUser(UserReferenceModel $userReference): ?User
    {
        $user = new User([
            'email'     => $userReference->email,
            'username'  => $userReference->username,
            'firstName' => $userReference->firstName,
            'lastName'  => $userReference->lastName,
        ]);

        if (!Craft::$app->getElements()->saveElement($user)) {
            return null;
        }

        $userReference->userId = $user->id;

        // Save reference
        if (!$this->saveReference($userReference)) {
            // @todo log error - and prevent moving forward?
        }

        //$command = craft()->db->createCommand();
        //$grp     = $command->select('id')->from('usergroups')->where(['handle' => $settings->groupHandle])->queryRow();

        $defaultGroup = LightweightAccess::$plugin->getSettings()->defaultUserGroup;

        if (empty($defaultGroup)) {
            // Assign them to the default user group
            Craft::$app->getUsers()->assignUserToDefaultGroup($user);
        }
        else {
            if (!\is_array($defaultGroup)) {
                $defaultGroup = [$defaultGroup];
            }

            $groupIds = array_map(function($handle) {
                $group = Craft::$app->getUserGroups()->getGroupByHandle($handle);

                if ($group) {
                    return $group->handle;
                }
            }, $defaultGroup);

            // @todo if user groups is a callable handle it there
            Craft::$app->getUsers()->assignUserToGroups($user->id, $groupIds);
        }

        return $user;
    }

    /**
     * Redirects to the URI specified in the POST.
     *
     * @param mixed       $object  Object containing properties that should be parsed for in the URL.
     * @param string|null $default The default URL to redirect them to, if no 'redirect' parameter exists. If this is left
     *                             null, then the current request’s path will be used.
     *
     * @return YiiResponse
     * @throws BadRequestHttpException if the redirect param was tampered with
     * @throws \Throwable
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     */
    public function redirectToPostedUrl($object = null, string $default = null): YiiResponse
    {
        $requestService = Craft::$app->getRequest();
        $url            = $requestService->getValidatedBodyParam('redirect');

        if ($url === null) {
            if ($default !== null) {
                $url = $default;
            }
            else {
                $url = $requestService->getPathInfo();
            }
        }

        if ($object) {
            $url = Craft::$app->getView()->renderObjectTemplate($url, $object);
        }

        return $this->redirect($url);
    }

    /**
     * @inheritdoc
     * @return YiiResponse
     */
    public function redirect($url, $statusCode = 302): YiiResponse
    {
        if (is_string($url)) {
            $url = UrlHelper::url($url);
        }

        if ($url !== null) {
            return Craft::$app->getResponse()->redirect($url, $statusCode)->send();
        }

        return $this->goHome();
    }

    /**
     * Redirects the browser to the home page.
     *
     * You can use this method in an action by returning the [[Response]] directly:
     *
     * ```php
     * // stop executing this action and redirect to home page
     * return $this->goHome();
     * ```
     *
     * @return YiiResponse the current response object
     */
    public function goHome()
    {
        return Craft::$app->getResponse()->redirect(Craft::$app->getHomeUrl())->send();
    }

    public function _createQuery(): Query
    {
        return (new Query())
            ->select([
                'id',
                'uid',
                'userId',
                'email',
                'username',
                'firstName',
                'lastName',
                'reference',
            ])
            ->from(UserReferenceRecord::TABLE_NAME);
    }

    public function saveReference(UserReferenceModel $reference): bool
    {
        if ($reference->id) {
            $record = UserReferenceRecord::findOne($reference->id);

            if (!$record) {
                throw new Exception('Invalid user reference ID: ' . $reference->id);
            }
        }
        else {
            $record            = new UserReferenceRecord();
            $record->setAttributes($reference->getAttributes([
                'userId',
                'email',
                'username',
                'firstName',
                'lastName',
                'reference',
            ]), false);

        }

        if (!$reference->validate()) {
            return false;
        }

        if (!$record->save()) {
            $reference->addErrors($record->getErrors());

            return false;
        }

        return true;
    }
}
