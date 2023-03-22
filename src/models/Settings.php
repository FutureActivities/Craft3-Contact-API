<?php

namespace futureactivities\contactapi\models;

use craft\base\Model;

class Settings extends Model
{
    public $email = 'email@example.com';
    public $recaptchaSecretKey = '';
    public $attachmentUploadLocationSource = '';
    public $attachmentUploadLocationSubpath = '';

    public function rules(): array
    {
        return [
            [['email'], 'required'],
        ];
    }
    
    public function getEmail(): string
    {
        return Craft::parseEnv($this->email);
    }
}