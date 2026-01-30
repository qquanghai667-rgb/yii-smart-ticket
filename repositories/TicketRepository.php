<?php
namespace app\repositories;

use app\models\Ticket;
use yii\web\NotFoundHttpException;

class TicketRepository 
{
    public function save(Ticket $ticket): bool 
    {
        return $ticket->save();
    }

    public function findById($id): Ticket 
    {
        if (($model = Ticket::findOne($id)) !== null) {
            return $model;
        }
        throw new NotFoundHttpException("Ticket not found.");
    }
}