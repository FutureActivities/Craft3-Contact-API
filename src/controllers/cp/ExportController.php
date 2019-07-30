<?php

namespace futureactivities\contactapi\controllers\cp;

use Craft;
use craft\web\Controller;
use yii\web\Response;
use craft\helpers\UrlHelper;
use futureactivities\contactapi\elements\Contact;
use futureactivities\contactapi\Plugin;

class ExportController extends Controller
{
    public function actionIndex(): Response
    {
        $data = $this->getMessages();
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=messages.csv');
        $output = fopen('php://output', 'w');
        
        fputcsv($output, $data['headings']);
        
        foreach ($data['messages'] AS $message) {
            $row = [];
            foreach($data['headings'] AS $key)
                $row[] = isset($message[$key]) ? $message[$key] : '';

            fputcsv($output, $row);
        }
        
        die();
    }
    
    protected function getMessages()
    {
        $headings = ['ID','dateCreated','fromName','fromEmail','subject','recipient'];
        $messages = [];
        
        $saved = Contact::find()->all();
        foreach($saved AS $message) {
            $result = [
                'ID' => $message->id,
                'dateCreated' => $message->dateCreated->format('Y-m-d H:i:s'),
                'fromName' => $message->fromName,
                'fromEmail' => $message->fromEmail,
                'subject' => $message->subject,
                'recipient' => $message->recipient
            ];
            
            $data = json_decode($message->data);
            foreach($data AS $key=>$value) {
                if (!in_array($key, $headings))
                    $headings[] = $key;
                
                $result[$key] = $value;
            }
            
            $messages[] = $result;
        }
        
        return [
            'headings' => $headings,
            'messages' => $messages
        ];
        
    }
}