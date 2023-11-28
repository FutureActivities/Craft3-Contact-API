<?php
namespace futureactivities\contactapi\elements\db;

use craft\db\Query;
use craft\elements\db\ElementQuery;
use craft\helpers\Db;
use futureactivities\contactapi\elements\Contact;

class ContactQuery extends ElementQuery
{
    public $siteId;
    public $fromName;
    public $fromEmail;
    public $subject;
    public $recipient;
    
    public function siteId($value)
    {
        $this->siteId = $value;

        return $this;
    }
    
    public function fromName($value)
    {
        $this->fromName = $value;

        return $this;
    }
    
    public function fromEmail($value)
    {
        $this->fromEmail = $value;

        return $this;
    }

    public function subject($value)
    {
        $this->subject = $value;

        return $this;
    }

    public function recipient($value)
    {
        $this->recipient = $value;

        return $this;
    }
    
    protected function beforePrepare(): bool
    {
        // join in the products table
        $this->joinElementTable('contact_messages');

        // select the price column
        $this->query->select([
            'contact_messages.siteId',
            'contact_messages.fromName',
            'contact_messages.fromEmail',
            'contact_messages.subject',
            'contact_messages.recipient',
            'contact_messages.data',
            'contact_messages.attachments',
        ]);
        
        if ($this->siteId) {
            $this->subQuery->andWhere(Db::parseParam('contact_messages.siteId', $this->siteId));
        }
        
        if ($this->fromName) {
            $this->subQuery->andWhere(Db::parseParam('contact_messages.fromName', $this->fromName));
        }
        
        if ($this->fromEmail) {
            $this->subQuery->andWhere(Db::parseParam('contact_messages.fromEmail', $this->fromEmail));
        }
        
        if ($this->subject) {
            $this->subQuery->andWhere(Db::parseParam('contact_messages.subject', $this->subject));
        }

        if ($this->recipient) {
            $this->subQuery->andWhere(Db::parseParam('contact_messages.recipient', $this->recipient));
        }
        
        return parent::beforePrepare();
    }
}