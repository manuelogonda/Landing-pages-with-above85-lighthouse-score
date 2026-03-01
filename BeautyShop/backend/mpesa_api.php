<?php
/**
 * LUMINÉ BEAUTY SHOP - M-Pesa API Wrapper
 * Safaricom Daraja API Integration
 * Documentation: https://developer.safaricom.co.ke/
 */

class MpesaAPI {
    private $consumerKey;
    private $consumerSecret;
    private $businessShortCode;
    private $passkey;
    private $environment;
    private $accessToken;

    public function __construct() {
        $this->consumerKey = MPESA_CONSUMER_KEY;
        $this->consumerSecret = MPESA_CONSUMER_SECRET;
        $this->businessShortCode = MPESA_BUSINESS_SHORTCODE;
        $this->passkey = MPESA_PASSKEY;
        $this->environment = MPESA_ENVIRONMENT;
    }

    // ==========================================
    // GET ACCESS TOKEN
    // ==========================================
    private function getAccessToken() {
        // Check if token is cached and still valid
        if (isset($this->accessToken) && !empty($this->accessToken)) {
            return $this->accessToken;
        }

        $url = $this->getBaseUrl() . '/oauth/v1/generate?grant_type=client_credentials';

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt(
            $curl,
            CURLOPT_HTTPHEADER,
            ['Content-Type: application/json; charset=utf8']
        );

        // Authenticate using base64
        curl_setopt(
            $curl,
            CURLOPT_USERPWD,
            $this->consumerKey . ':' . $this->consumerSecret
        );

        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($curl);
        $httpStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($httpStatus !== 200) {
            logAction('TOKEN_ERROR', "Failed to get access token (HTTP $httpStatus)", json_decode($response, true));
            return null;
        }

        $result = json_decode($response, true);

        if (isset($result['access_token'])) {
            $this->accessToken = $result['access_token'];
            return $this->accessToken;
        }

        logAction('TOKEN_ERROR', 'No access token in response', $result);
        return null;
    }

    // ==========================================
    // INITIATE STK PUSH
    // ==========================================
    public function initiateStkPush($phoneNumber, $amount, $accountReference, $transactionDesc) {
        $accessToken = $this->getAccessToken();

        if (!$accessToken) {
            return [
                'ResponseCode' => '1',
                'ResponseDescription' => 'Failed to authenticate with M-Pesa'
            ];
        }

        $url = $this->getBaseUrl() . '/mpesa/stkpush/v1/processrequest';

        // Format timestamp
        $timestamp = date('YmdHis');

        // Generate password: base64(shortcode + passkey + timestamp)
        $password = base64_encode($this->businessShortCode . $this->passkey . $timestamp);

        $payload = [
            'BusinessShortCode' => $this->businessShortCode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => 'CustomerPayBillOnline',
            'Amount' => (int)$amount,
            'PartyA' => $phoneNumber,
            'PartyB' => $this->businessShortCode,
            'PhoneNumber' => $phoneNumber,
            'CallBackURL' => MPESA_CALLBACK_URL,
            'AccountReference' => $accountReference,
            'TransactionDesc' => $transactionDesc
        ];

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt(
            $curl,
            CURLOPT_HTTPHEADER,
            [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json; charset=utf8'
            ]
        );

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_TIMEOUT, 15);

        $response = curl_exec($curl);
        $httpStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        $result = json_decode($response, true);

        logAction('STK_PUSH', "STK Push Request (HTTP $httpStatus)", [
            'phone' => $phoneNumber,
            'amount' => $amount,
            'reference' => $accountReference,
            'response' => $result
        ]);

        return $result;
    }

    // ==========================================
    // QUERY STK PUSH STATUS
    // ==========================================
    public function queryStkPushStatus($checkoutRequestID) {
        $accessToken = $this->getAccessToken();

        if (!$accessToken) {
            return false;
        }

        $url = $this->getBaseUrl() . '/mpesa/stkpush/v1/query';

        $timestamp = date('YmdHis');
        $password = base64_encode($this->businessShortCode . $this->passkey . $timestamp);

        $payload = [
            'BusinessShortCode' => $this->businessShortCode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'CheckoutRequestID' => $checkoutRequestID
        ];

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt(
            $curl,
            CURLOPT_HTTPHEADER,
            [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json; charset=utf8'
            ]
        );

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_TIMEOUT, 15);

        $response = curl_exec($curl);
        curl_close($curl);

        return json_decode($response, true);
    }

    // ==========================================
    // GET BASE URL
    // ==========================================
    private function getBaseUrl() {
        if ($this->environment === 'production') {
            return 'https://api.safaricom.co.ke';
        }
        return 'https://sandbox.safaricom.co.ke';
    }

    // ==========================================
    // PROCESS M-PESA CALLBACK
    // ==========================================
    public static function processCallback($data) {
        if (!isset($data['Body']['stkCallback'])) {
            return false;
        }

        $stkCallback = $data['Body']['stkCallback'];
        $resultCode = $stkCallback['ResultCode'];
        $resultDesc = $stkCallback['ResultDesc'];

        // Result code 0 = success
        if ($resultCode == 0) {
            $callbackMetadata = $stkCallback['CallbackMetadata']['Item'];
            
            $paymentData = [
                'merchant_request_id' => $stkCallback['MerchantRequestID'],
                'checkout_request_id' => $stkCallback['CheckoutRequestID'],
                'result_code' => $resultCode,
                'result_desc' => $resultDesc,
                'amount' => 0,
                'mpesa_receipt' => '',
                'transaction_date' => '',
                'phone_number' => ''
            ];

            // Extract callback metadata
            foreach ($callbackMetadata as $item) {
                switch ($item['Name']) {
                    case 'Amount':
                        $paymentData['amount'] = $item['Value'];
                        break;
                    case 'MpesaReceiptNumber':
                        $paymentData['mpesa_receipt'] = $item['Value'];
                        break;
                    case 'TransactionDate':
                        $paymentData['transaction_date'] = $item['Value'];
                        break;
                    case 'PhoneNumber':
                        $paymentData['phone_number'] = $item['Value'];
                        break;
                }
            }

            return $paymentData;
        }

        logAction('PAYMENT_FAILED', "M-Pesa payment failed: $resultDesc", ['result_code' => $resultCode]);
        return false;
    }
}

?>
