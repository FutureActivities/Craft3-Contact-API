<?php
namespace futureactivities\contactapi\services;

use yii\base\Component;
use futureactivities\contactapi\Plugin;

class Recaptcha extends Component
{
    public function verify($response)
	{
        $base = "https://www.google.com/recaptcha/api/siteverify";
        $settings = Plugin::getInstance()->settings;
        
        $params = [
        	'secret' =>  $settings->recaptchaSecretKey,
        	'response' => $response
        ];
        
        $client = new \GuzzleHttp\Client();
        $result = $client->request('POST', $base, [
            'form_params' => $params
        ]);
         
        if ($result->getStatusCode() != 200)
            return false;
        
        $json = json_decode($result->getBody()->getContents());
        return $json->success;
	}
}