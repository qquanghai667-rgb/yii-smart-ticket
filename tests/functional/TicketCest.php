<?php

use app\models\Ticket;

class TicketCest
{
    public function testCreateTicketSuccessfully(FunctionalTester $I)
    {
        $I->amGoingTo('submit a support ticket via API');
        
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPost('/ticket/create', [
            'title' => 'Test Ticket via Codeception',
            'description' => 'This is a functional test description.'
        ]);

    
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['status' => 'success']);

        
        $ticketId = $I->grabDataFromResponseByJsonPath('$.ticket_id')[0];

        
        $ticket = \Yii::$app->db->createCommand("SELECT * FROM ticket WHERE id = :id", [
            ':id' => $ticketId
        ])->queryOne();

        $I->assertNotEmpty($ticket, "Ticket should exist in database");
        $I->assertEquals('Test Ticket via Codeception', $ticket['title']);
        $I->assertEquals('Open', $ticket['status']);

        
        $queueJob = \Yii::$app->db->createCommand("SELECT * FROM queue WHERE job LIKE :match", [
            ':match' => '%"ticketId";i:' . $ticketId . ';%'
        ])->queryOne();

        $I->assertNotEmpty($queueJob, "A queue job should be created for this ticket");
        $I->assertEquals('queue', $queueJob['channel']);
    }
}