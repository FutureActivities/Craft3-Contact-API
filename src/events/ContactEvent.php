<?php
namespace futureactivities\contactapi\events;

use craft\base\FieldInterface;
use yii\base\Event;

class ContactEvent extends Event
{
    // Properties
    // =========================================================================
    
    /**
     * @var mixed The contact model
     */
    public $contact = null;
    
    /**
     * @var array The response
     */
    public $response;
}
