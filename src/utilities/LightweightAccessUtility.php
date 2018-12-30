<?php
/**
 * Lightweight Access plugin for Craft CMS 3.x
 *
 * LDAP SSO for Craft
 *
 * @link      https://superbig.co
 * @copyright Copyright (c) 2018 Superbig
 */

namespace superbig\lightweightaccess\utilities;

use superbig\lightweightaccess\LightweightAccess;
use superbig\lightweightaccess\assetbundles\lightweightaccessutilityutility\LightweightAccessUtilityUtilityAsset;

use Craft;
use craft\base\Utility;

/**
 * Lightweight Access Utility
 *
 * @author    Superbig
 * @package   LightweightAccess
 * @since     1.0.0
 */
class LightweightAccessUtility extends Utility
{
    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('lightweight-access', 'LightweightAccessUtility');
    }

    /**
     * @inheritdoc
     */
    public static function id(): string
    {
        return 'lightweightaccess-lightweight-access-utility';
    }

    /**
     * @inheritdoc
     */
    public static function iconPath()
    {
        return Craft::getAlias("@superbig/lightweightaccess/assetbundles/lightweightaccessutilityutility/dist/img/LightweightAccessUtility-icon.svg");
    }

    /**
     * @inheritdoc
     */
    public static function badgeCount(): int
    {
        return 0;
    }

    /**
     * @inheritdoc
     */
    public static function contentHtml(): string
    {
        Craft::$app->getView()->registerAssetBundle(LightweightAccessUtilityUtilityAsset::class);

        $someVar = 'Have a nice day!';
        return Craft::$app->getView()->renderTemplate(
            'lightweight-access/_components/utilities/LightweightAccessUtility_content',
            [
                'someVar' => $someVar
            ]
        );
    }
}
