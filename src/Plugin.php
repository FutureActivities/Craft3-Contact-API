<?php
namespace futureactivities\contactapi;

use yii\base\Event;
use craft\web\UrlManager;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterCpNavItemsEvent;
use craft\web\twig\variables\Cp;

class Plugin extends \craft\base\Plugin
{
    public $hasCpSettings = true;
    public $hasCpSection = true;
    
    public function init()
    {
        parent::init();
        
        // Register our site routes
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_SITE_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['rest/v1/contact'] = 'contactapi/v1/contact';
                $event->rules['rest/v1/contact/<id>'] = 'contactapi/v1/contact/entry';
            }
        );
        
        // Register a custom CP route
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function(RegisterUrlRulesEvent $event) {
            $event->rules['contactapi/<messageId:\d+>'] = 'contactapi/cp/view';
            $event->rules['contactapi/export'] = 'contactapi/cp/export';
        });
        
        $this->setComponents([
            'recaptcha' => \futureactivities\contactapi\services\Recaptcha::class,
        ]);
    }
    
    public function getCpNavItem()
    {
        $item = parent::getCpNavItem();
        $item['label'] = 'Contact';
        $item['icon'] = '@futureactivities/contactapi/icon.svg';
        return $item;
    }
    
    public function registerCpUrlRules(RegisterUrlRulesEvent $event)
    {
        $rules = [
            'contactapi/<messageId:\d+>' => 'contactapi/cp/view'
        ];
        $event->rules = array_merge($event->rules, $rules);
    }
    
    protected function createSettingsModel()
    {
        return new \futureactivities\contactapi\models\Settings();
    }
    
    protected function settingsHtml()
    {
        return \Craft::$app->getView()->renderTemplate('contactapi/settings', [
            'settings' => $this->getSettings()
        ]);
    }
}