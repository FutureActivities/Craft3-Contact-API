<?php

namespace futureactivities\contactapi\models;

use craft\base\Model;

class Settings extends Model
{
    public $email = 'email@example.com';
    public $recaptchaSecretKey = '';

    public function rules()
    {
        return [
            [['email'], 'required'],
        ];
    }
}