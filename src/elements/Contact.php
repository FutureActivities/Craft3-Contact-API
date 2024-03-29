<?php
namespace futureactivities\contactapi\elements;

use craft\base\Element;
use craft\elements\db\ElementQueryInterface;
use futureactivities\contactapi\elements\db\ContactQuery;
use craft\helpers\UrlHelper;
use craft\elements\User;

class Contact extends Element
{
    /**
     * @var integer Site ID
     */
    public ?int $siteId;
    
    /**
     * @var string From Name
     */
    public $fromName;
    
    /**
     * @var string From Email
     */
    public $fromEmail;
    
    /**
     * @var string Subject
     */
    public $subject;

    /**
     * @var string Recipient
     */
    public $recipient;

    /**
     * @var mixed The contact form data
     */
    public $data;
    
    /**
     * @var string The contact form attachments
     */
    public $attachments;
    
    public static function refHandle(): ?string
    {
        return 'contactapi';
    }
    
    
    public function afterSave(bool $isNew): void
    {
        if ($isNew) {
            \Craft::$app->db->createCommand()
                ->insert('{{%contact_messages}}', [
                    'id' => $this->id,
                    'siteId' => $this->siteId,
                    'fromName' => $this->fromName,
                    'fromEmail' => $this->fromEmail,
                    'subject' => $this->subject,
                    'data' => json_encode($this->data),
                    'attachments' => implode(',', $this->attachments),
                    'recipient' => $this->recipient ?? 'N/A',
                ])
                ->execute();
        } else {
            \Craft::$app->db->createCommand()
                ->update('{{%contact_messages}}', [
                    'siteId' => $this->siteId,
                    'fromName' => $this->fromName,
                    'fromEmail' => $this->fromEmail,
                    'subject' => $this->subject,
                    'recipient' => $this->recipient ?? 'N/A',
                    'data' => json_encode($this->data),
                    'attachments' => implode(',', $this->attachments)
                ], ['id' => $this->id])
                ->execute();
        }
    
        parent::afterSave($isNew);
    }
    
    public static function find(): ElementQueryInterface
    {
        return new ContactQuery(static::class);
    }
    
    protected static function defineTableAttributes(): array
    {
        return [
            'fromName' => 'From Name',
            'subject' => 'Subject',
            'recipient' => 'Recipient',
            'dateCreated' => 'Date Submitted'
        ];
    }
    
    protected static function defineSearchableAttributes(): array
    {
        return [
            'fromName',
            'subject',
            'recipient'
        ];
    }
    
    protected static function defineSources(string $context = null): array
    {
        $sources = [
            [
                'key' => '*',
                'label' => 'All Messages'
            ]
        ];
        
        $contacts = \Craft::$app->db->createCommand('SELECT subject FROM {{%contact_messages}} GROUP BY(subject)')->queryAll();
        foreach($contacts AS $contact) {
            $sources[] = [
                'key' => $contact['subject'],
                'label' => $contact['subject'],
                'criteria' => [
                    'subject' => $contact['subject']    
                ]
            ];
        }
        
        return $sources;
    }
    
    public function getCpEditUrl(): ?string
    {
        return UrlHelper::cpUrl('contactapi/'.$this->id);
    }
    
    public function canView(User $user):bool 
    {
        return true;
    }
    
    public static function isLocalized(): bool
    {
        return true;
    }
    
    public function getSupportedSites(): array
    {
        return [$this->siteId];
    }
}
