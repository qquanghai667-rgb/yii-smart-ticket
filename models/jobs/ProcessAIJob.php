<?php

namespace app\models\jobs;

use Yii;
use yii\base\BaseObject;
use yii\queue\JobInterface;
use app\models\Ticket;
use GuzzleHttp\Client;

/**
 * Job class to process tickets using Groq AI
 */
class ProcessAIJob extends BaseObject implements JobInterface
{
    /** @var int ID of the ticket to be processed */
    public $ticketId;

    /** @var string Groq API Key fetched from environment variables */
    private $apiKey;

    /**
     * Executes the job
     * @param \yii\queue\Queue $queue
     */
    public function execute($queue)
    {
        $this->apiKey = getenv('GROQ_API_KEY');
        // Fetch the ticket from database
        $ticket = Ticket::findOne($this->ticketId);
        if (!$ticket) {
            return;
        }

        $logFile = Yii::getAlias('@runtime/logs/ticket_flow.log');
        $timestamp = date('Y-m-d H:i:s');
        $logMsg = "[$timestamp] [Ticket ID {$this->ticketId}] STEP 3: Groq AI Processing Started.\n";

        try {
            $client = new Client();
            
            // Prepare the prompt for the AI
            $prompt = "Analyze the following support ticket:
                Title: {$ticket->title}
                Description: {$ticket->description}
                
                Return a JSON object with the following fields:
                - category: (Technical, Billing, or General)
                - sentiment: (Positive, Negative, or Neutral)
                - urgency: (High, Medium, or Low)
                - reply: (A polite response in English)";

            // Send request to Groq API
            $response = $client->post("https://api.groq.com/openai/v1/chat/completions", [
                'headers' => [
                    'Authorization' => "Bearer {$this->apiKey}",
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'model' => 'llama-3.3-70b-versatile',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are a helpful assistant that always outputs JSON.'
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt
                        ]
                    ],
                    // Enable JSON mode to ensure valid JSON response
                    'response_format' => ['type' => 'json_object']
                ]
            ]);

            // Decode API response
            $result = json_decode($response->getBody()->getContents(), true);
            $content = $result['choices'][0]['message']['content'] ?? '';
            $data = json_decode($content, true);

            $logMsg .= "[$timestamp] [Ticket ID {$this->ticketId}] AI RESULT: " . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n";

            if ($data) {
                // Update ticket fields with AI analysis results
                $ticket->category = $data['category'] ?? 'General';
                $ticket->sentiment = $data['sentiment'] ?? 'Neutral';
                $ticket->urgency = $data['urgency'] ?? 'Medium';
                $ticket->suggested_reply = $data['reply'] ?? '';
                $ticket->status = 'Processed';

                if ($ticket->save()) {
                    $logMsg .= "[$timestamp] [Ticket ID {$this->ticketId}] STEP 4: DB Update Success. Flow Completed.\n";
                } else {
                    $logMsg .= "[$timestamp] [Ticket ID {$this->ticketId}] STEP 4 ERROR: Validation failed. " . json_encode($ticket->errors) . "\n";
                }
            } else {
                $logMsg .= "[$timestamp] [Ticket ID {$this->ticketId}] ERROR: Failed to parse AI JSON response.\n";
            }

        } catch (\Exception $e) {
            // Log any errors occurred during API call or processing
            $logMsg .= "[$timestamp] [Ticket ID {$this->ticketId}] AI FATAL ERROR: " . $e->getMessage() . "\n";
        }

        // Finalize log entry
        $logMsg .= "--------------------------------------------------------------------------------\n";
        file_put_contents($logFile, $logMsg, FILE_APPEND);
    }
}