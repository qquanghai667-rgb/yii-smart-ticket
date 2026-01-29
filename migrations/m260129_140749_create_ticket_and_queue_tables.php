<?php

use yii\db\Migration;

class m260129_140749_create_ticket_and_queue_tables extends Migration
{
    public function safeUp()
    {
        // 1. Tạo bảng Ticket
        $this->createTable('{{%ticket}}', [
            'id' => $this->primaryKey(),
            'title' => $this->string()->notNull(),
            'description' => $this->text()->notNull(),
            'status' => $this->string()->defaultValue('Open'),
            'category' => $this->string(),
            'sentiment' => $this->string(),
            'urgency' => $this->string(),
            'suggested_reply' => $this->text(),
            'created_at' => $this->integer(),
        ]);

        // 2. Tạo bảng Queue
        $this->createTable('{{%queue}}', [
            'id' => $this->primaryKey(),
            'channel' => $this->string()->notNull(),
            'job' => $this->binary()->notNull(),
            'pushed_at' => $this->integer()->notNull(),
            'ttr' => $this->integer()->notNull(),
            'delay' => $this->integer()->defaultValue(0)->notNull(),
            'priority' => $this->integer()->unsigned()->defaultValue(1024)->notNull(),
            'reserved_at' => $this->integer(),
            'attempt' => $this->integer(),
            'done_at' => $this->integer(),
        ]);

        $this->createIndex('channel', '{{%queue}}', 'channel');
        $this->createIndex('reserved_at', '{{%queue}}', 'reserved_at');
        $this->createIndex('priority', '{{%queue}}', 'priority');
    }

    public function safeDown()
    {
        $this->dropTable('{{%queue}}');
        $this->dropTable('{{%ticket}}');
    }
}