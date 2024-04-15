<?php

namespace App\Libraries;

use App\Helpers\CURLHelpers;
use App\Models\Report;

class WhatsApp
{
    protected $apiUrl;
    protected $device_token;
    protected $account_token;

    public function __construct()
    {
        $this->apiUrl = getenv('whatsapp.api.url');
        $this->device_token = getenv('whatsapp.api.device_token');
        $this->account_token = getenv('whatsapp.api.account_token');
    }

    // TODO:
    // 3. check status message -------> Need webhook url in Dashboard WhatsApp API

    // DONE:
    // 1. send message (sendMassage)
    // 2. get QR code (getQRCode)
    // 4. validate number (validateNumber)
    // 5. get devices (getDevices)

    /**
     * Send message to WhatsApp
     *
     * @param string $number
     * @param string $message
     * @param string|null $country_code
     * @param string|null $url
     * @param mixed $file
     * @param array|null $buttonAttr
     * @return array
     */
    public function sendMassage(string $number, string $message, ?string $country_code, ?string $url, mixed $file, ?array $buttonAttr)
    {
        $buttonJSON = null;

        if (isset($buttonAttr)) {
            $buttonJSON = json_encode($buttonAttr);

            $postData = [
                'target' => $number,
                'url' => $url ?? 'https://id-live-01.slatic.net/p/6a78913c131cfcd539813bd4b7c42459.png',
                'file' => $file ?? null,
                'buttonJSON' => $buttonJSON,
                'countryCode' => $country_code
            ];
        } else {
            $postData = [
                'target' => $number,
                'message' => $message,
                'file' => $file ?? null,
                'url' => $url ?? 'https://id-live-01.slatic.net/p/6a78913c131cfcd539813bd4b7c42459.png',
                'countryCode' => $country_code
            ];
        }

        $data = CURLHelpers::curl($this->apiUrl . '/send', $postData, 'POST', $this->device_token);

        $error_msg = $data['error_msg'];
        $response = $data['response'];

        if (isset($error_msg)) {
            return [
                'error' => $error_msg['status'],
                'message' => $error_msg['reason']
            ];
        } else {
            if ($response['status']) {
                $report = new Report();

                foreach ($response["id"] as $k => $v) {
                    $target = $response["target"][$k];
                    $status = $response["process"];

                    $report->insert([
                        'id' => $v,
                        'target' => $target,
                        'message' => $message,
                        'status' => $status
                    ]);
                }

                return [
                    'error' => false,
                    'message' => 'Message sent successfully',
                    'detail' => $response['detail'],
                    'messageId' => $response['id'],
                    'process' => $response['process'],
                    'target' => $response['target']
                ];
            } else {
                return [
                    'error' => true,
                    'message' => $response['reason']
                ];
            }
        }
    }

    /**
     * Get QR Code
     * @param string|null $type
     * @param string|null $whatsapp
     *
     * @return array
     */
    public function getQRCode(?string $type, ?string $whatsapp)
    {
        $postData = [
            'type' => $type ?? 'qr',
            'whatsapp' => $whatsapp ?? null
        ];

        $data = CURLHelpers::curl($this->apiUrl . '/qr', $postData, 'POST', $this->device_token);

        $error_msg = $data['error_msg'];
        $response = $data['response'];

        if (isset($error_msg)) {
            return [
                'error' => $error_msg['status'],
                'message' => $error_msg['reason']
            ];
        } else {
            if ($response['status']) {
                return [
                    'error' => false,
                    'code' => $response['code'],
                    'consent' => $response['consent'],
                    'status' => $response['status'],
                    'url' => $response['url'],
                ];
            } else {
                return [
                    'error' => true,
                    'message' => $response['reason']
                ];
            }
        }
    }

    /**
     * Check status message (for endpoint webhook)
     *
     * @return array
     */
    public function checkStatusMessage()
    {
        $report = new Report();

        header('Content-Type: application/json; charset=utf-8');

        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        $device = $data['device'];
        $id = $data['id'];
        $stateid = $data['stateid'];
        $status = $data['status'];
        $state = $data['state'];

        //update status and state
        if (isset($id) && isset($stateid)) {
            $report->update($id, [
                'status' => $status,
                'state' => $state,
                'stateid' => $stateid
            ]);
        } else if (isset($id) && !isset($stateid)) {
            $report->update($id, [
                'status' => $status
            ]);
        } else {
            $report->update($stateid, [
                'state' => $state
            ]);
        }

        // check affected rows
        if ($report->affectedRows() > 0) {
            return [
                'error' => false,
                'message' => 'Status updated successfully'
            ];
        } else {
            return [
                'error' => true,
                'message' => 'Failed to update status'
            ];
        }
    }

    /**
     * Validate number
     *
     * @param array $numbers
     * @param string|null $countryCode
     * @return array
     */
    public function validateNumber(string $numbers, ?string $countryCode)
    {
        $postData = [
            'target' => $numbers,
            'countryCode' => $countryCode ?? '62'
        ];

        $data = CURLHelpers::curl($this->apiUrl . '/validate', $postData, 'POST', $this->device_token);

        $response = $data['response'];

        if ($response['status']) {
            return [
                'error' => false,
                'not_registered' => $response['not_registered'],
                'registered' => $response['registered'],
            ];
        } else {
            return [
                'error' => true,
                'message' => $response['reason']
            ];
        }
    }

    /**
     * Get device
     *
     * @return array
     */
    public function getDevices()
    {
        $data = CURLHelpers::curl($this->apiUrl . '/get-devices', null, 'POST', $this->account_token);

        $response = $data['response'];

        if ($response['status']) {
            return [
                'error' => false,
                'connected' => $response['connected'],
                'device_list' => $response['data'],
                'devices' => $response['devices'],
                'messages' => $response['messages'],
                'status' => $response['status'],
                'type' => $response['type'],
            ];
        } else {
            return [
                'error' => true,
                'message' => $response['reason']
            ];
        }
    }

    /**
     * Disconnect device
     *
     * @return array
     */
    public function disconnectDevice()
    {
        $data = CURLHelpers::curl($this->apiUrl . '/disconnect', null, 'POST', $this->device_token);

        $error_msg = $data['error_msg'];
        $response = $data['response'];

        if (isset($error_msg)) {
            $error_msg = json_decode($error_msg, true);
            return [
                'error' => $error_msg['status'],
                'message' => $error_msg['reason']
            ];
        } else {
            if ($response['status']) {
                return [
                    'error' => false,
                    'status' => $response['status'],
                    'detail' => $response['detail'] ?? null,
                    'reason' => $response['reason'] ?? null,
                ];
            } else {
                return [
                    'error' => true,
                    'message' => $response['reason']
                ];
            }
        }
    }
}
