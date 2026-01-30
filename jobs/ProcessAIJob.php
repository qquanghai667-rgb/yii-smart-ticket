<?php

namespace app\jobs;

use Yii;
use yii\base\BaseObject;
use yii\queue\RetryableJobInterface;
use app\models\Ticket;
use GuzzleHttp\Client;

class ProcessAIJob extends BaseObject implements RetryableJobInterface
{
    /** @var int */
    public $ticketId;

    /**
     * TTR (Time to Run) in seconds
     */
    public function getTtr()
    {
        return 60; 
    }

    /**
     * Retry if job failed
     */
    public function canRetry($attempt, $error)
    {
        return $attempt < 3;
    }

    public function execute($queue)
    {
        $ticket = Ticket::findOne($this->ticketId);
        if (!$ticket) {
            return;
        }

        Yii::info("STEP 3: Groq AI Processing Started for Ticket ID: {$this->ticketId}", 'ticket_flow');

        try {
            $apiKey = getenv('GROQ_API_KEY');
            $client = new Client(['timeout' => 30]);

            $prompt = $this->generatePrompt($ticket);

            $response = $client->post("https://api.groq.com/openai/v1/chat/completions", [
                'headers' => [
                    'Authorization' => "Bearer {$apiKey}",
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'model' => 'llama-3.3-70b-versatile',
                    'messages' => [
                        ['role' => 'system', 'content' => 'You are a helpful assistant that always outputs JSON.'],
                        ['role' => 'user', 'content' => $prompt]
                    ],
                    'response_format' => ['type' => 'json_object']
                ]
            ]);

            $result = json_decode($response->getBody()->getContents(), true);
            $data = json_decode($result['choices'][0]['message']['content'] ?? '{}', true);

            if (empty($data)) {
                throw new \Exception("Empty AI response for Ticket ID: {$this->ticketId}");
            }

            Yii::info("AI RESULT for ID {$this->ticketId}: " . json_encode($data), 'ticket_flow');

            $this->updateTicket($ticket, $data);

        } catch (\Exception $e) {
            Yii::error("AI FATAL ERROR (Ticket ID {$this->ticketId}): " . $e->getMessage(), 'ticket_flow');
            throw $e; 
        }
    }

    /**
     * Tách logic generate prompt (Architecture)
     */
    private function generatePrompt($ticket)
    {
        return "Analyze the following support ticket:
                Title: {$ticket->title}
                Description: {$ticket->description}
                
                Return a JSON object:
                - category: (Technical, Billing, or General)
                - sentiment: (Positive, Negative, or Neutral)
                - urgency: (High, Medium, or Low)
                - reply: (A polite response in English)";
    }

    /**
     * logic update DB (Architecture)
     */
    private function updateTicket(Ticket $ticket, array $data)
    {
        $ticket->category = $data['category'] ?? 'General';
        $ticket->sentiment = $data['sentiment'] ?? 'Neutral';
        $ticket->urgency = $data['urgency'] ?? 'Medium';
        $ticket->suggested_reply = $data['reply'] ?? '';
        $ticket->status = Ticket::STATUS_PROCESSED;

        if ($ticket->save()) {
            Yii::info("STEP 4: DB Update Success for ID: {$this->ticketId}. Flow Completed.", 'ticket_flow');
        } else {
            Yii::error("STEP 4 ERROR (ID {$this->ticketId}): Validation failed. " . json_encode($ticket->errors), 'ticket_flow');
        }
    }
}