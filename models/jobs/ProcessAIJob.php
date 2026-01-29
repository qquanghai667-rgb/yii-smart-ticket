<?php
namespace app\jobs;

use app\models\Ticket;
use yii\base\BaseObject;
use yii\queue\JobInterface;

class ProcessAIJob extends BaseObject implements JobInterface
{
    public $ticketId;

    public function execute($queue)
    {
        $ticket = Ticket::findOne($this->ticketId);
        if (!$ticket) return;

        // --- Prompt Strategy ---
        $systemPrompt = "You are a Customer Support AI. Analyze the ticket and return ONLY a JSON object. 
        Format: {\"category\": \"Technical|Billing|General\", \"sentiment\": \"Positive|Neutral|Negative\", \"urgency\": \"Low|Medium|High\", \"reply\": \"Draft response\"}";
        
        $userContent = "Title: {$ticket->title}\nDescription: {$ticket->description}";

        // Giả lập gọi API (Bạn có thể thay bằng OpenAI Guzzle request)
        sleep(2); 
        $mockJson = '{"category": "Technical", "sentiment": "Negative", "urgency": "High", "reply": "We are sorry for the inconvenience. Our team is investigating the issue."}';
        
        $result = json_decode($mockJson, true);

        // Lưu vào database
        $ticket->attributes = [
            'category' => $result['category'],
            'sentiment' => $result['sentiment'],
            'urgency' => $result['urgency'],
            'suggested_reply' => $result['reply'],
            'status' => 'Processed'
        ];
        $ticket->save();
    }
}