<?php
namespace futureactivities\contactapi;

use yii\base\Event;
use craft\web\UrlManager;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterCpNavItemsEvent;
use craft\web\twig\variables\Cp;
use craft\elements\Asset;
use craft\helpers\Html;
use craft\log\MonologTarget;
use Monolog\Formatter\LineFormatter;
use Psr\Log\LogLevel;
use Craft;

class Plugin extends \craft\base\Plugin
{
    public bool $hasCpSettings = true;
    public bool $hasCpSection = true;
    public string $schemaVersion = '1.1.0';
    
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
            $event->rules['contactapi/<elementId:\d+>'] = 'contactapi/cp/view';
            $event->rules['contactapi/export'] = 'contactapi/cp/export';
        });
        
        $this->setComponents([
            'recaptcha' => \futureactivities\contactapi\services\Recaptcha::class,
            'assets' => \futureactivities\contactapi\services\Assets::class,
        ]);
        
        Craft::getLogger()->dispatcher->targets[] = new MonologTarget([
            'name' => 'contactapi',
            'categories' => ['contactapi'],
            'level' => LogLevel::INFO,
            'logContext' => false,
            'allowLineBreaks' => false,
            'formatter' => new LineFormatter(
                format: "%datetime% %message%\n",
                dateFormat: 'Y-m-d H:i:s',
            ),
        ]);
    }
    
    public function getCpNavItem(): ?array
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
    
    protected function createSettingsModel(): ?\craft\base\Model
    {
        return new \futureactivities\contactapi\models\Settings();
    }
    
    protected function settingsHtml(): ?string
    {
        return \Craft::$app->getView()->renderTemplate('contactapi/settings', [
            'settings' => $this->getSettings(),
            'sourceOptions' => $this->getSourceOptions()
        ]);
    }
    
    protected function getSourceOptions(): array
    {
        $sourceOptions = [];

        foreach (Asset::sources('settings') as $key => $volume) {
            if (!isset($volume['heading'])) {
                $sourceOptions[] = [
                    'label' => Html::encode($volume['label']),
                    'value' => $volume['key']
                ];
            }
        }

        return $sourceOptions;
    }
}