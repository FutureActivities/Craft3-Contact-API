<?php
namespace futureactivities\contactapi\migrations;

use craft\db\Migration;

class Install extends Migration
{
    public function safeUp()
    {
        if (!$this->db->tableExists('{{%contact_messages}}')) {
            // create the products table
            $this->createTable('{{%contact_messages}}', [
                'id' => $this->integer()->notNull(),
                'siteId' => $this->integer()->null(),
                'fromName' => $this->char(255)->notNull(),
                'fromEmail' => $this->char(255)->notNull(),
                'subject' => $this->char(255)->notNull(),
                'recipient' => $this->char(255)->notNull(),
                'data' => $this->text(),
                'attachments' => $this->text(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
                'PRIMARY KEY(id)',
            ]);
        
            // give it a FK to the elements table
            $this->addForeignKey(
                $this->db->getForeignKeyName('{{%contact_messages}}', 'id'),
                '{{%contact_messages}}', 'id', '{{%elements}}', 'id', 'CASCADE', null);
        }
    }

    public function safeDown()
    {
        // ...
    }
}