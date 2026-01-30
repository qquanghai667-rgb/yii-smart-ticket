<?php
namespace app\controllers;

use Yii;
use yii\rest\Controller;
use app\models\Ticket;
use app\models\jobs\ProcessAIJob;

class TicketController extends Controller
{
    public $enableCsrfValidation = false;

    public function actionCreate()
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $model = new Ticket();
        $model->load(Yii::$app->request->post(), '');
        if ($model->load(\Yii::$app->request->post(), '')) {
            if ($model->save()) {
            $logFile = Yii::getAlias('@runtime/logs/ticket_flow.log');
            $timestamp = date('Y-m-d H:i:s');
            
            // Step 1: Log Save Ticket
            $logMsg = "[$timestamp] [Ticket ID {$model->id}] STEP 1: Save ticket success.\n";
            
            try {
                $job = new \app\models\jobs\ProcessAIJob(['ticketId' => $model->id]);
                $queueId = Yii::$app->queue->push($job);
                
                // Step 2: Log Save Queue
                $logMsg .= "[$timestamp] [Ticket ID {$model->id}] STEP 2: Push to Queue success. Queue ID: $queueId\n";
                
                file_put_contents($logFile, $logMsg, FILE_APPEND);

                return [
                    'status' => 'success',
                    'ticket_id' => $model->id,
                    'queue_job_id' => $queueId,
                ];
            } catch (\Exception $e) {
                $logMsg .= "[$timestamp] [Ticket ID {$model->id}] STEP 2 ERROR: " . $e->getMessage() . "\n";
                file_put_contents($logFile, $logMsg, FILE_APPEND);
                return ['status' => 'error', 'message' => $e->getMessage()];
            }
            } else {
            
                throw new \yii\web\ServerErrorHttpException(
                    json_encode($model->errors) . " | DB Error: " . print_r($model->getErrors(), true)
                );
            }
        }
        
        return [
        'status' => 'error_validation',
        'errors' => $model->getErrors(),
        'debug_raw_body' => $rawBody, 
        'debug_post_data' => $postData
    ];
    }
}