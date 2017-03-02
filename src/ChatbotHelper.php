<?php

namespace App;


use Dotenv\Dotenv;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class ChatbotHelper
{

    public $config;
    protected $chatbotAI;
    protected $facebookSend;
    protected $log;
    private $accessToken;

    public function __construct()
    {
        $dotenv = new Dotenv(__DIR__ . '/../');
        $dotenv->load();
        $this->accessToken = getenv('PAGE_ACCESS_TOKEN');
        $this->config = require __DIR__ . '/config.php';
        $this->chatbotAI = new ChatbotAI($this->config);
        $this->facebookSend = new FacebookSend();
        $this->log = new Logger('general');
        $this->log->pushHandler(new StreamHandler('debug.log'));
    }

    /**
     * Get the sender id of the message
     * @param $input
     * @return mixed
     */
    public function getSenderId($input)
    {
        return $input['entry'][0]['messaging'][0]['sender']['id'];
    }

    /**
     * Get the user's message from input
     * @param $input
     * @return mixed
     */
    public function getMessage($input)
    {
        return $input['entry'][0]['messaging'][0]['message']['text'];
    }

    /**
     * Check if the callback is a user message
     * @param $input
     * @return bool
     */
    public function isMessage($input)
    {
        return isset($input['entry'][0]['messaging'][0]['message']['text']) && !isset
            ($input['entry'][0]['messaging'][0]['message']['is_echo']);

    }

    /**
     * Get the answer to a given user's message
     * @param null $api
     * @param string $message
     * @return string
     */
    public function getAnswer($message, $api = null)
    {

        if ($api === 'apiai') {
            return $this->chatbotAI->getApiAIAnswer($message);
        } elseif ($api === 'witai') {
            return $this->chatbotAI->getWitAIAnswer($message);
        } elseif ($api === 'rates') {
            return $this->chatbotAI->getForeignExchangeRateAnswer($message);
        } else {
            return $this->chatbotAI->getAnswer($message);
        }

    }

    /**
     * Send a reply back to Facebook chat
     * @param $senderId
     * @param $replyMessage
     */
    public function send($senderId, string $replyMessage)
    {
        $this->facebookSend->send($this->accessToken, $senderId, $replyMessage);
    }

    /**
     * Verify Facebook webhook
     * This is only needed when you setup or change the webhook
     * @param $request
     * @return mixed
     */
    public function verifyWebhook($request)
    {
        if (!isset($request['hub_challenge'])) {
            return false;
        };

        $hubVerifyToken = null;
        $hubVerifyToken = $request['hub_verify_token'];
        $hubChallenge = $request['hub_challenge'];

        if (null !== $hubChallenge && $hubVerifyToken === $this->config['webhook_verify_token']) {

            echo $hubChallenge;
        }


    }
}