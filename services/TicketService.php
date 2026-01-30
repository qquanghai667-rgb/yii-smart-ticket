<?php
namespace app\services;

use Yii;
use app\models\Ticket;
use app\jobs\ProcessAIJob;
use app\repositories\TicketRepository;
use yii\base\Exception;

class TicketService
{
    private $repository;

    /**
     * Dependency Injection Constructor
     * 
     */
    public function __construct(TicketRepository $repository)
    {
        $this->repository = $repository;
    }

    public function createTicket(array $data)
    {
        $model = new Ticket();
        $model->load($data, '');


        $transaction = Yii::$app->db->beginTransaction();
        try {
            if (!$this->repository->save($model)) {
                throw new Exception("Validation failed: " . json_encode($model->getErrors()));
            }

            Yii::info("STEP 1: Save ticket success. Ticket ID: {$model->id}", 'ticket_flow');


            $job = new ProcessAIJob(['ticketId' => $model->id]);
            $queueId = Yii::$app->queue->push($job);

            if (!$queueId) {
                throw new Exception("Unable to push job to Queue.");
            }

            Yii::info("STEP 2: Push to Queue success. Queue ID: $queueId", 'ticket_flow');

    
            $transaction->commit();

            return [
                'ticket_id' => $model->id,
                'queue_job_id' => $queueId
            ];
        } catch (\Exception $e) {
        
            $transaction->rollBack();
            
            Yii::error("Ticket Creation Error: " . $e->getMessage(), 'ticket_flow');
            throw $e;
        }
    }
}