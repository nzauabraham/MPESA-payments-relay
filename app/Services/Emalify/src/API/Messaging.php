<?php
namespace App\Services\Emalify\src\API;

use App\Services\Emalify\src\API\Abstracts\AbstractAPI;
use App\Services\Emalify\src\Support\NetworkMapper;

class Messaging extends AbstractAPI
{

    /**
     * Send message
     *
     * This metthod accepts an Array of recipients and optional parameters like
     *
     * $options = [
     *  'messageId' => 'unique-message-id',
     *  'callback' => 'http://example.com/dlr/callback',
     *  'from' => 'SenderId'
     * ]
     *
     * @see https://docs.emalify.com/?version=latest#e31e965b-2e80-4ab0-8191-fdd78f972aa9
     * @throws \Exception
     */
    public function sendMessage($message, array $recipients, array $options = [])
    {
        $endpoint = $this->buildEndpoint('sms/simple/send');
        $body = array_merge($options, [
            'message' => $message,
            'to' => $recipients
        ]);

        return $this->makeRequest('POST', $endpoint, $body);
    }


    public function sendBulkMessages($payload, array $options = [])
    {
        $messages = $this->validateBulkPayLoad($payload);
        $params = array_merge([
            'messages' => $messages,
        ], $options);
        $endpoint = $this->buildEndpoint('sms/bulk');

        try {
            return $this->makeRequest('POST', $endpoint, $params);
        } catch (\Exception $e) {
        }
    }

    /**
     * @param $messageId
     * @return mixed
     */
    public function getDeliveryReport($messageId)
    {
        $endpoint = $this->buildEndpoint('sms/delivery-reports?messageId='.$messageId);

        return $this->makeRequest('GET', $endpoint, []);
    }

    /**
     * @param array $payload
     * @return array
     */
    private function validateBulkPayLoad(array $payload)
    {
        return array_filter($payload, function ($message) {
            return is_array($message) &&
            key_exists('message', $message) &&
            key_exists('recipient', $message) &&
            NetworkMapper::getNetwork($message['recipient']);
        });
    }
}
