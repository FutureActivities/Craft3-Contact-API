<?php 
namespace futureactivities\contactapi\controllers\v1;

use Craft;
use craft\web\Controller;
use yii\rest\ActiveController;
use futureactivities\rest\Plugin as API;
use futureactivities\rest\errors\BadRequestException;
use futureactivities\rest\traits\ActionRemovable;
use futureactivities\navapi\data\NavDataProvider;
use futureactivities\contactapi\elements\Contact;
use futureactivities\contactapi\Plugin;
use craft\mail\Message;
use craft\web\UploadedFile;

class ContactController extends Controller
{
    protected $allowAnonymous = true;
    
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
        
        $this->saveContact($settings->email, $request->post(), $attachments);
        $this->sendEmail($settings->email, $request->post(), $attachments);
        
        return $this->asJson(['success' => true]);
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
            
        $entry = \craft\elements\Entry::find()
            ->id($id)
            ->one();
            
        if (!$entry)
            throw new \Exception('Invalid request.');
        
        $sendTo = $settings->email;
        
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
        if (isset($entry->emailAddress))
            $sendTo = $entry->emailAddress;
        
        $attachments = $this->processAttachments();
        
        $this->saveContact($sendTo, $request->post(), $attachments);
        $this->sendEmail($sendTo, $request->post(), $attachments);
        
        return $this->asJson(['success' => true]);
    }
    
    public function saveContact($to, $data, $attachments = [])
    {
        $settings = Craft::$app->systemSettings->getSettings('email');
        
        $contact = new Contact();
        $contact->fromName = isset($data['fromName']) ? $data['fromName'] : $settings['fromName'];
        $contact->fromEmail = isset($data['fromEmail']) ? $data['fromEmail'] : $settings['fromEmail'];
        $contact->subject = isset($data['subject']) ? $data['subject'] : 'Contact Form Enquiry';
        $contact->recipient = $to;
        
        unset($data['subject'], $data['fromName'], $data['fromEmail'], $data['g-recaptcha-response']);
        
        $contact->data = $data;
        $contact->attachments = array_map(function($asset) {
            return $asset->id;
        }, $attachments);
        
        Craft::$app->elements->saveElement($contact);
    }
    
    protected function sendEmail($to, $data, $attachments = [])
    {
        $subject = isset($data['subject']) ? $data['subject'] : 'Contact Form Enquiry';
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
        $message->setFrom([$settings['fromEmail'] => $settings['fromName']]);
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