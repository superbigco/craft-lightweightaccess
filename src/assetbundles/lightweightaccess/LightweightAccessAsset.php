<?php
/**
 * Lightweight Access plugin for Craft CMS 3.x
 *
 * LDAP SSO for Craft
 *
 * @link      https://superbig.co
 * @copyright Copyright (c) 2018 Superbig
 */

namespace superbig\lightweightaccess\assetbundles\LightweightAccess;

use Craft;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * @author    Superbig
 * @package   LightweightAccess
 * @since     1.0.0
 */
class LightweightAccessAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = "@superbig/lightweightaccess/assetbundles/lightweightaccess/dist";

        $this->depends = [
            CpAsset::class,
        ];

        $this->js = [
            'js/LightweightAccess.js',
        ];

        $this->css = [
            'css/LightweightAccess.css',
        ];

        parent::init();
    }
}
