<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Libraries\OutputResponse;
use App\Libraries\WhatsApp;
use CodeIgniter\HTTP\Response;

class WhatsAppAPI extends BaseController
{
    protected $whatsapp;

    public function __construct()
    {
        $this->whatsapp = new WhatsApp();
    }

    public function index()
    {
        try {
            $req = $this->request->getVar('req');

            if (!isset($req) || empty($req) || $req === '') {
                // check request method
                if ($this->request->getMethod() == 'get') {
                    return OutputResponse::sendResponse(null, 'API is running. You need to change method type to procced. Method not allowed', Response::HTTP_METHOD_NOT_ALLOWED);
                } else {
                    return OutputResponse::sendResponse(null, 'Request not found', Response::HTTP_BAD_REQUEST);
                }
            }

            switch ($req) {
                case 'send':
                    $this->send();
                    break;

                case 'getQrCode':
                    $this->getQrCode();
                    break;

                case 'validateNumber':
                    $this->validateNumber();
                    break;

                case 'getDevices':
                    $this->getDevices();
                    break;

                case 'disconnectDevice':
                    $this->disconnectDevice();
                    break;

                default:
                    return OutputResponse::sendResponse(null, 'Request not found', Response::HTTP_BAD_REQUEST);
                    break;
            }
        } catch (\Exception $e) {
            log_message('error', $e->getTraceAsString());
            return OutputResponse::sendResponse(null, $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function send()
    {
        $validation = \Config\Services::validation();

        $validation->setRules([
            'number' => 'required',
            'message' => 'required',
            'countryCode' => 'required',

            'url' => 'permit_empty',
            'file' => 'permit_empty|uploaded[file]|max_size[file,1024]|ext_in[file,png,jpg,jpeg]',
            'footer' => 'permit_empty',
            'button1' => 'permit_empty',
            'button2' => 'permit_empty',
            'button3' => 'permit_empty',

            'buttonAttr' => 'permit_empty'
        ]);

        if (!$validation->withRequest($this->request)->run()) {
            return OutputResponse::sendResponse(null, $validation->getErrors(), Response::HTTP_BAD_REQUEST);
        }

        $number = $this->request->getVar('number');
        $message = $this->request->getVar('message');
        $country_code = $this->request->getVar('countryCode');
        $url = $this->request->getVar('url');
        $file = $this->request->getFile('file');
        $footer = $this->request->getVar('footer');
        $button1 = $this->request->getVar('button1');
        $button2 = $this->request->getVar('button2');
        $button3 = $this->request->getVar('button3');
        $buttonAttr = $this->request->getVar('buttonAttr');

        if (isset($buttonAttr) && !empty($buttonAttr)) {
            $buttonAttr = json_decode($buttonAttr, true);
            $buttonAttr['message'] = $message ?? $buttonAttr['message'];
        } else {
            if (isset($footer) && !empty($footer)) {
                $buttonAttr['footer'] = $footer;
                $buttonAttr['message'] = $message;
            }

            if (isset($button1) && !empty($button1)) {
                $buttonAttr['buttons'][0]['id'] = rand(1000, 9999);
                $buttonAttr['buttons'][0]['message'] = $button1;
            }

            if (isset($button2) && !empty($button2)) {
                $buttonAttr['buttons'][1]['id'] = rand(1000, 9999);
                $buttonAttr['buttons'][1]['message'] = $button2;
            }

            if (isset($button3) && !empty($button3)) {
                $buttonAttr['buttons'][2]['id'] = rand(1000, 9999);
                $buttonAttr['buttons'][2]['message'] = $button3;
            }
        }

        $response = $this->whatsapp->sendMassage($number, $message, $country_code, $url, $file, $buttonAttr);

        if ($response['error']) {
            return OutputResponse::sendResponse(null, 'Failed to send message. ' . $response['message'], Response::HTTP_INTERNAL_SERVER_ERROR);
        } else {
            return OutputResponse::sendResponse($response, 'Message sent successfully.', Response::HTTP_OK);
        }
    }

    public function getQrCode()
    {
        $validation = \Config\Services::validation();

        $validation->setRules([
            'type' => 'permit_empty|in_list[qr,code]',
            'whatsapp' => 'permit_empty'
        ]);

        if (!$validation->withRequest($this->request)->run()) {
            return OutputResponse::sendResponse(null, $validation->getErrors(), Response::HTTP_BAD_REQUEST);
        }

        $type = $this->request->getVar('type');
        $whatsapp = $this->request->getVar('whatsapp');

        $response = $this->whatsapp->getQrCode($type, $whatsapp);

        if ($response['error']) {
            return OutputResponse::sendResponse(null, 'Failed to get QR Code. ' . $response['message'], Response::HTTP_INTERNAL_SERVER_ERROR);
        } else {
            return OutputResponse::sendResponse($response, 'QR Code generated successfully.', Response::HTTP_OK);
        }
    }

    public function validateNumber()
    {
        $validation = \Config\Services::validation();

        $validation->setRules([
            'numbers' => 'required',
            'countryCode' => 'required'
        ]);

        if (!$validation->withRequest($this->request)->run()) {
            return OutputResponse::sendResponse(null, $validation->getErrors(), Response::HTTP_BAD_REQUEST);
        }

        $number = $this->request->getVar('numbers');
        $country_code = $this->request->getVar('countryCode');

        $response = $this->whatsapp->validateNumber($number, $country_code);

        if ($response['error']) {
            return OutputResponse::sendResponse(null, 'Failed to validate numbers. ' . $response['message'], Response::HTTP_INTERNAL_SERVER_ERROR);
        } else {
            return OutputResponse::sendResponse($response, 'Number validated successfully.', Response::HTTP_OK);
        }
    }

    public function getDevices()
    {
        $response = $this->whatsapp->getDevices();

        if ($response['error']) {
            return OutputResponse::sendResponse(null, 'Failed to get devices. ' . $response['message'], Response::HTTP_INTERNAL_SERVER_ERROR);
        } else {
            return OutputResponse::sendResponse($response, 'Devices fetched successfully.', Response::HTTP_OK);
        }
    }

    public function disconnectDevice()
    {
        $response = $this->whatsapp->disconnectDevice();

        if ($response['error']) {
            return OutputResponse::sendResponse(null, 'Failed to disconnect device. ' . $response['message'], Response::HTTP_INTERNAL_SERVER_ERROR);
        } else {
            return OutputResponse::sendResponse($response, $response['reason'] ?? $response['detail'], Response::HTTP_OK);
        }
    }

    public function checkStatusMessage()
    {
        try {
            $response = $this->whatsapp->checkStatusMessage();

            if ($response['error']) {
                return OutputResponse::sendResponse(null, 'Failed to check status message. ' . $response['message'], Response::HTTP_INTERNAL_SERVER_ERROR);
            } else {
                return OutputResponse::sendResponse(null, 'Status message checked successfully.', Response::HTTP_OK);
            }
        } catch (\Exception $e) {
            log_message('error', $e->getTraceAsString());
            return OutputResponse::sendResponse(null, $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
