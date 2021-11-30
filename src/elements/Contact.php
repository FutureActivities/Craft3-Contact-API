<?php
namespace futureactivities\contactapi\elements;

use craft\base\Element;
use craft\elements\db\ElementQueryInterface;
use futureactivities\contactapi\elements\db\ContactQuery;

class Contact extends Element
{
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
    
    public function afterSave(bool $isNew)
    {
        if ($isNew) {
            \Craft::$app->db->createCommand()
                ->insert('{{%contact_messages}}', [
                    'id' => $this->id,
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
            'id' => \Craft::t('app', 'ID'),
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
        
        $contacts = \Craft::$app->db->createCommand('SELECT subject FROM craft_contact_messages GROUP BY(subject)')->queryAll();
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
    
    public function getCpEditUrl()
    {
        return 'contactapi/'.$this->id;
    }
}