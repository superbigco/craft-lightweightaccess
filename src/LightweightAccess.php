<?php
/**
 * Lightweight Access plugin for Craft CMS 3.x
 *
 * LDAP SSO for Craft
 *
 * @link      https://superbig.co
 * @copyright Copyright (c) 2018 Superbig
 */

namespace superbig\lightweightaccess;

use craft\controllers\UsersController;
use craft\elements\User;
use craft\events\UserEvent;
use superbig\lightweightaccess\services\LightweightAccessService as LightweightAccessServiceService;
use superbig\lightweightaccess\variables\LightweightAccessVariable;
use superbig\lightweightaccess\models\Settings;
use superbig\lightweightaccess\utilities\LightweightAccessUtility as LightweightAccessUtilityUtility;

use Craft;
use craft\base\Plugin;
use craft\services\Plugins;
use craft\events\PluginEvent;
use craft\console\Application as ConsoleApplication;
use craft\web\UrlManager;
use craft\services\Utilities;
use craft\web\twig\variables\CraftVariable;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUrlRulesEvent;

use yii\base\Event;

/**
 * Class LightweightAccess
 *
 * @author    Superbig
 * @package   LightweightAccess
 * @since     1.0.0
 *
 * @property  LightweightAccessServiceService $lightweightAccessService
 * @method  Settings getSettings()
 */
class LightweightAccess extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * @var LightweightAccess
     */
    public static $plugin;

    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public $schemaVersion = '1.0.0';
    public $hasCpSettings = true;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        $this->setComponents([
            "lightweightAccessService" => "superbig\\lightweightaccess\\services\\LightweightAccessService",
        ]);

        if (Craft::$app instanceof ConsoleApplication) {
            $this->controllerNamespace = 'superbig\lightweightaccess\console\controllers';
        }

        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_SITE_URL_RULES,
            function(RegisterUrlRulesEvent $event) {
                $event->rules['siteActionTrigger1'] = 'lightweight-access/default';
            }
        );

        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function(RegisterUrlRulesEvent $event) {
                $event->rules['cpActionTrigger1'] = 'lightweight-access/default/do-something';
            }
        );

        Event::on(
            Utilities::class,
            Utilities::EVENT_REGISTER_UTILITY_TYPES,
            function(RegisterComponentTypesEvent $event) {
                $event->types[] = LightweightAccessUtilityUtility::class;
            }
        );

        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function(Event $event) {
                /** @var CraftVariable $variable */
                $variable = $event->sender;
                $variable->set('lightweightAccess', LightweightAccessVariable::class);
            }
        );

        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_INSTALL_PLUGIN,
            function(PluginEvent $event) {
                if ($event->plugin === $this) {
                }
            }
        );

        Event::on(
            UsersController::class,
            UsersController::EVENT_LOGIN_FAILURE,
            [self::$plugin->lightweightAccessService, 'onLoginFailure']
        );

        Craft::info(
            Craft::t(
                'lightweight-access',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function createSettingsModel()
    {
        return new Settings();
    }

    /**
     * @inheritdoc
     */
    protected function settingsHtml(): string
    {
        return Craft::$app->view->renderTemplate(
            'lightweight-access/settings',
            [
                'settings' => $this->getSettings(),
            ]
        );
    }
}
