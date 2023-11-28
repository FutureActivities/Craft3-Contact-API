<?php

namespace futureactivities\contactapi\migrations;

use Craft;
use craft\db\Migration;

/**
 * m220106_155057_siteid migration.
 */
class m220106_155057_siteid extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        if ($this->db->tableExists('{{%contact_messages}}') && !$this->db->columnExists('{{%contact_messages}}', 'siteId')) {
            $this->addColumn('{{%contact_messages}}', 'siteId', $this->integer()->after('id')->null());
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m220106_155057_siteid cannot be reverted.\n";
        return false;
    }
}
