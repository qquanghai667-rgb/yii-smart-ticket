
# 🎫 Yii2 AI-Powered Smart Ticket System

  

An automated support ticket management system integrated with **Groq AI (Llama 3)** to perform real-time sentiment analysis, ticket categorization, and automated response generation.

  

---

  

## 🚀 Key Features

*  **Asynchronous Processing:** Uses Yii2 Queue (DB Driver) to handle AI tasks without slowing down the user experience.

*  **Groq AI Integration:** Leverages Llama 3 for near-instant ticket analysis and Vietnamese response suggestions.

*  **Full Flow Logging:** Every step is tracked in a dedicated log file (`ticket_flow.log`) mapped by Ticket ID.

*  **Containerized Architecture:** Fully dockerized for consistent development and deployment environments.

  

---

  

## 🛠 Prerequisites

* [Docker](https://www.docker.com/) & [Docker Compose](https://docs.docker.com/compose/)

* [Postman](https://www.postman.com/) (for API testing)

*  **Groq API Key:** Obtain yours from the [Groq Cloud Console](https://console.groq.com/).

  

---

  

## 📥 Installation & Setup

  

### 1. Environment Configuration

Clone the repository and create your local environment file:

    cp  .env.example  .env

  
Then in the .env file, put the key you register from Grog here

    GROQ_API_KEY=gsk_your_actual_key_here

  

### 2. Launch Services

Start  the  application,  database,  and  background  worker  containers:



    docker-compose up -d

  

### 3. Initialize Application

Install PHP dependencies and run the database migrations:
**Install Composer packages**

    docker-compose  exec  app  composer  install
**Run migrations (creates ticket and queue tables)**

    docker-compose  exec  app  php  yii  migrate
---

## 🧠 AI Intelligence & Strategy

### Prompt Strategy
Our system uses a **System-Role Prompting** strategy. By defining the AI as a "Support Assistant that outputs JSON," we ensure the response is always machine-readable. We use explicit constraints such as `MUST be in English` and `response_format: {type: json_object}` to prevent the AI from returning conversational filler or incorrect languages.

### Context Awareness
The AI doesn't just categorize; it performs **Sentiment-Urgency Mapping**. It analyzes the emotional tone of the user's description (e.g., "frustrated", "urgent", "broken") to dynamically assign urgency. 
- **Negative Sentiment + System Down** → `High Urgency`
- **Neutral Sentiment + Question** → `Medium/Low Urgency`

---
  ## 📡 Usage Guide (Postman)

  

To  simulate  a  new  customer  support  request,  send  a  POST  request:

  

Endpoint:  http://localhost:8000/ticket/create

  

Method:  POST

  

Headers:  Content-Type:  application/json

  

Body:

  

JSON

    {
    
    "title":  "Application Login Issue",
    
    "description":  "I receive a 403 error whenever I try to access the dashboard since this morning."
    
    }

Expected  Response:

JSON

    {
    
    "status":  "success",
    
    "ticket_id":  27,
    
    "queue_job_id":  12
    
    }

  

## 📝 Logging & Flow Tracking

  

The  system  logs  the  entire  lifecycle  of  a  ticket.  You  can  search  for  a  specific [Ticket ID  XXXX]  to  see  the  full  flow.

  

View  Real-time  Logs

Monitor  the  AI  processing  live  via  terminal  to  see  the  "Step-by-Step"  flow:

  

Bash

    docker-compose  exec  app  tail  -f  runtime/logs/ticket_flow.log

Filter  Logs  by  Ticket  ID

To  track  the  specific  journey  of  Ticket  #27:

    docker-compose  exec  app  grep  "Ticket ID 27"  runtime/logs/ticket_flow.log

Log  Structure  Detail:

STEP  1:  Ticket  successfully  saved  to  MySQL.

  

STEP  2:  Task  pushed  to  queue  table  with  a  unique  Job  ID.

  

STEP  3:  Background  Worker  picks  up  the  job,  sends  data  to  Groq  AI.

  

STEP  4:  AI  response (JSON) is parsed and saved back to the Ticket record.

  
## 📂  Project  Architecture

1 - controllers/TicketController.php  -  API  Entry  point & Queue     dispatcher. 

2 - models/jobs/ProcessAIJob.php  -  Core  Logic:     Communicates  with  Groq  AI  and  updates  the  DB.

3 - docker-compose.yml  -  Defines  services  including  the  background queue-worker. 

4 - .env  -  Secure  storage  for  sensitive  API  keys    (ignored by  Git)

---

## 🧪 Automated Testing

### Running Tests inside Docker
To ensure the system is functioning correctly, you can execute the functional tests using the following command:

    docker-compose exec app php vendor/bin/codecept run functional TicketCest

### Why do we need this test?

-   **API Verification:** Ensures the Controller correctly handles JSON payloads and prevents `500 Internal Server Error`.
    
-   **Database Integrity:** Confirms that the Ticket record is actually persisted in the MySQL database.
    
-   **Queue Validation:** Verifies the system has successfully dispatched the AI processing task to the `queue` table.