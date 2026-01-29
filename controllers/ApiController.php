<?php
namespace app\controllers;

use Yii;
use yii\rest\Controller;
use app\models\Ticket;
use app\jobs\ProcessAIJob;

class ApiController extends Controller
{
    public function actionCreate()
    {
        $model = new Ticket();
        $model->attributes = Yii::$app->request->post();

        if ($model->save()) {
            
            Yii::$app->queue->push(new ProcessAIJob(['ticketId' => $model->id]));
            
            return [
                'message' => 'Ticket created successfully. AI is processing...',
                'ticket_id' => $model->id
            ];
        }
        Yii::$app->response->statusCode = 422;
        return $model->errors;
    }

    public function actionView($id)
    {
        return Ticket::findOne($id) ?: ['error' => 'Not found'];
    }
}