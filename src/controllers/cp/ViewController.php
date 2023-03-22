<?php

namespace futureactivities\contactapi\controllers\cp;

use Craft;
use craft\web\Controller;
use yii\web\Response;
use craft\helpers\UrlHelper;
use futureactivities\contactapi\elements\Contact;
use futureactivities\contactapi\Plugin;

class ViewController extends Controller
{
    public function actionIndex(int $elementId = null): Response
    {
        $variables = [];
        
        if (!$elementId)
            throw new \Exception('Missing element ID');
        
        // Load message
        $variables['message'] = Contact::find()->id($elementId)->one();
        
        // Breadcrumbs
        $variables['crumbs'] = [
            [
                'label' => Craft::t('contactapi', 'Contact'),
                'url' => UrlHelper::url('contactapi')
            ]
        ];
        
        // Set the base CP edit URL
        $variables['baseCpEditUrl'] = 'contactapi/{id}';
        
        return $this->renderTemplate('contactapi/_view', $variables);
    }
}