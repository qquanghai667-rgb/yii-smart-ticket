<?php
namespace app\controllers;

use Yii;
use yii\rest\Controller;
use app\services\TicketService;
use yii\web\Response;
use yii\web\UnprocessableEntityHttpException;

class TicketController extends Controller
{
    // Dependency Injection qua constructor là kỹ năng Middle+
    private $ticketService;

    public function __construct($id, $module, TicketService $ticketService, $config = [])
    {
        $this->ticketService = $ticketService;
        parent::__construct($id, $module, $config);
    }

    public function actionCreate()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $postData = Yii::$app->request->post();

        try {
            $result = $this->ticketService->createTicket($postData);
            return array_merge(['status' => 'success'], $result);
        } catch (\Exception $e) {
            // Trả về HTTP Code 422 cho lỗi nghiệp vụ thay vì 200
            throw new UnprocessableEntityHttpException($e->getMessage());
        }
    }
}