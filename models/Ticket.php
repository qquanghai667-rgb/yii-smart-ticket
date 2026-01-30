<?php
namespace app\models;

use yii\db\ActiveRecord;

class Ticket extends ActiveRecord
{
    const STATUS_OPEN = 'Open';
    const STATUS_PROCESSED = 'Processed';

    const URGENCY_LOW = 'Low';
    const URGENCY_MEDIUM = 'Medium';
    const URGENCY_HIGH = 'High';
    
    public static function tableName() { return '{{%ticket}}'; }

    public function rules() {
        return [
            [['title', 'description'], 'required'],
            [['description', 'suggested_reply'], 'string'],
            [['status'], 'default', 'value' => self::STATUS_OPEN],
            [['category', 'sentiment', 'urgency'], 'string', 'max' => 50],
        ];
    }
}