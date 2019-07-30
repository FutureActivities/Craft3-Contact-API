<?php

namespace futureactivities\contactapi\migrations;

use Craft;
use craft\db\Migration;

/**
 * m190730_140037_attachments migration.
 */
class m190730_140037_attachments extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        if ($this->db->tableExists('{{%contact_messages}}') && !$this->db->columnExists('{{%contact_messages}}', 'attachments')) {
            $this->addColumn('{{%contact_messages}}', 'attachments', $this->text()->after('data')->null());
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m190730_140037_attachments cannot be reverted.\n";
        return false;
    }
}
