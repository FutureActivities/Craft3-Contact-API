<?php 
namespace futureactivities\contactapi\controllers\v1;

use Craft;
use craft\web\Controller;
use yii\rest\ActiveController;
use futureactivities\contactapi\elements\Contact;
use futureactivities\contactapi\Plugin;
use craft\mail\Message;
use craft\web\UploadedFile;
use futureactivities\contactapi\events\ContactEvent;

class ContactController extends Controller
{
    const EVENT_NEW_CONTACT = 'submitNewContact';
    
    protected $allowAnonymous = true;
    public $enableCsrfValidation = false;
    
    public function actionIndex()
    {
        $request = Craft::$app->getRequest();
        $settings = Plugin::getInstance()->settings;
        
        if (!$request->isPost)
            throw new \Exception('Invalid request.');
        
        // Check if we are using reCaptcha
        if ($settings->recaptchaSecretKey) {
            if (!$captcha = $request->getParam('g-recaptcha-response'))
                throw new \Exception('Missing reCaptcha response.');
                
            if (!Plugin::getInstance()->recaptcha->verify($captcha))
                throw new \Exception('Invalid reCaptcha.');
        }
        
        $attachments = $this->processAttachments();
        
        $sendTo = Craft::parseEnv($settings->email);
        
        // Save contact
        $contact = $this->saveContact($sendTo, $request->post(), $attachments);
        
        // Send contact email
        $this->sendEmail($sendTo, $request->post(), $attachments);
        
        // Output
        $response = ['success' => true];
        
        // Custom event
        $event = new ContactEvent([
            'contact' => $contact,
            'response' => $response
        ]);
        $this->trigger(self::EVENT_NEW_CONTACT, $event);
        
        return $this->asJson($event->response);
    }
    
    public function actionEntry($id)
    {
        $request = Craft::$app->getRequest();
        $settings = Plugin::getInstance()->settings;
        
        if (!$request->isPost)
            throw new \Exception('Invalid request.');
            
        // Check if we are using reCaptcha
        if ($settings->recaptchaSecretKey) {
            if (!$captcha = $request->getParam('g-recaptcha-response'))
                throw new \Exception('Missing reCaptcha response.');
                
            if (!Plugin::getInstance()->recaptcha->verify($captcha))
                throw new \Exception('Invalid reCaptcha.');
        }
        
        $postRequest = $request->post();
        $siteId = isset($postRequest['siteId']) ? $postRequest['siteId'] : '*'; 
        
        $entry = \craft\elements\Entry::find()
            ->siteId($siteId)
            ->id($id)
            ->one();
        
        if (!$entry)
            throw new \Exception('Invalid request.');
        
        $sendTo = Craft::parseEnv($settings->email);
        
        // Check for contact details Matrix field
        if (isset($entry->contactDetails)) {
            foreach ($entry->contactDetails AS $detail) {
                // Third-party Link Field plugin
                if (isset($detail->detailsValue) && $detail->detailsValue->type == 'email') {
                    $sendTo = $detail->detailsValue->value;
                    break;
                }
                
                // Plain text email field
                if (isset($detail->email)) {
                    $sendTo = $detail->email;
                    break;
                }
            }
        }
        
        // Check for generic email field
        if (isset($entry->emailAddress) && $entry->emailAddress)
            $sendTo = $entry->emailAddress;
        
        $attachments = $this->processAttachments();
        
        $contact = $this->saveContact($sendTo, $postRequest, $attachments);
        $this->sendEmail($sendTo, $postRequest, $attachments);
        
        // Output
        $response = ['success' => true];
        
        // Custom event
        $event = new ContactEvent([
            'contact' => $contact,
            'response' => $response
        ]);
        $this->trigger(self::EVENT_NEW_CONTACT, $event);
        
        return $this->asJson($event->response);
    }
    
    public function saveContact($to, $data, $attachments = [])
    {
        $settings = Craft::$app->systemSettings->getSettings('email');
        
        $contact = new Contact();
        $contact->siteId = isset($data['siteId']) ? $data['siteId'] : null;
        $contact->fromName = isset($data['fromName']) ? $data['fromName'] : Craft::parseEnv($settings['fromName']);
        $contact->fromEmail = isset($data['fromEmail']) ? $data['fromEmail'] : Craft::parseEnv($settings['fromEmail']);
        $contact->subject = isset($data['subject']) ? $data['subject'] : 'Contact Form Enquiry';
        $contact->recipient = $to;
        
        unset($data['siteId'], $data['subject'], $data['fromName'], $data['fromEmail'], $data['g-recaptcha-response']);
        
        $contact->data = $data;
        $contact->attachments = array_map(function($asset) {
            return $asset->id;
        }, $attachments);
        
        Craft::$app->elements->saveElement($contact);
        
        return $contact;
    }
    
    protected function sendEmail($to, $data, $attachments = [])
    {
        $subject = isset($data['subject']) ? $data['subject'] : 'Contact Form Enquiry';
        $replyTo = isset($data['fromEmail']) ? $data['fromEmail'] : null;
        unset($data['subject'], $data['fromName'], $data['fromEmail'], $data['g-recaptcha-response']);
        
        $oldMode = \Craft::$app->view->getTemplateMode();
        
        try {
            // Attempt to load contact template from local project
            \Craft::$app->view->setTemplateMode(\Craft::$app->view::TEMPLATE_MODE_SITE);
            $html = Craft::$app->getView()->renderTemplate("_contact", ['data' => $data]);
        } catch(\Exception $e) {
            // If not found, load the default one supplied by this plugin
            \Craft::$app->view->setTemplateMode(\Craft::$app->view::TEMPLATE_MODE_CP);
            $html = Craft::$app->getView()->renderTemplate("/contactapi/_email", ['data' => $data]);
        }
        
        $settings = Craft::$app->systemSettings->getSettings('email');
        
        $message = new Message();
        $message->setFrom([Craft::parseEnv($settings['fromEmail']) => Craft::parseEnv($settings['fromName'])]);
        if ($replyTo) $message->setReplyTo($replyTo);
        $message->setTo($to);
        $message->setSubject($subject);
        $message->setHtmlBody($html);
        
        foreach ($attachments AS $attachment)
            $message->attach($attachment->getUrl());
    
        Craft::$app->mailer->send($message);
        
        \Craft::$app->view->setTemplateMode($oldMode);
    }
    
    /**
     * Check for any attachments in the request
     */
    protected function processAttachments()
    {
        try {
            
            $settings = Plugin::getInstance()->settings;
            $folderId = Plugin::getInstance()->assets->resolveVolumePath($settings->attachmentUploadLocationSource, $settings->attachmentUploadLocationSubpath);
            
            $attachments = [];
            
            foreach($_FILES AS $field => $file) {
                if (is_array($file)) {
                    $uploaded = UploadedFile::getInstancesByName($field);
                    foreach($uploaded AS $upload) {
                        $asset = Plugin::getInstance()->assets->uploadNewAsset($upload, $folderId);
                        
                        if ($asset)
                            $attachments[] = $asset;
                    }
                }
            }
            
            return $attachments;
        
        } catch (\Exception $e) {
            
            return [];
            
        }
    }
}