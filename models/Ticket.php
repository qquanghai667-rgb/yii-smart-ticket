<?php
namespace app\models;

use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

class Ticket extends ActiveRecord
{
    public static function tableName() { return '{{%ticket}}'; }

    // public function behaviors() {
    //     return [
    //         [
    //             'class' => TimestampBehavior::class,
    //             'updatedAtAttribute' => false,
    //         ],
    //     ];
    // }

    public function rules() {
        return [
            [['title', 'description'], 'required'],
            [['description', 'suggested_reply'], 'string'],
            [['status'], 'default', 'value' => 'Open'],
            [['category', 'sentiment', 'urgency'], 'string', 'max' => 50],
        ];
    }
}