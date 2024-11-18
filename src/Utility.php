<?php

namespace Razorpay\Api;

use Requests;
use WpOrg\Requests\Hooks;

class Utility
{
    const SHA256 = 'sha256';

    public function verifyPaymentSignature($attributes)
    {
        $actualSignature = $attributes['razorpay_signature'];

        $paymentId = $attributes['razorpay_payment_id'];

        if (isset($attributes['razorpay_order_id']) === true)
        {
            $orderId = $attributes['razorpay_order_id'];

            $payload = $orderId . '|' . $paymentId;
        }
        else if (isset($attributes['razorpay_subscription_id']) === true)
        {
            $subscriptionId = $attributes['razorpay_subscription_id'];

            $payload = $paymentId . '|' . $subscriptionId;
        }
        else if (isset($attributes['razorpay_payment_link_id']) === true)
        {
            $paymentLinkId     = $attributes['razorpay_payment_link_id'];

            $paymentLinkRefId  = $attributes['razorpay_payment_link_reference_id'];

            $paymentLinkStatus = $attributes['razorpay_payment_link_status'];

            $payload = $paymentLinkId . '|'. $paymentLinkRefId . '|' . $paymentLinkStatus . '|' . $paymentId;
        }
        else
        {
            throw new Errors\SignatureVerificationError(
                'Either razorpay_order_id or razorpay_subscription_id or razorpay_payment_link_id must be present.');
        }

        $secret = Api::getSecret();

        self::verifySignature($payload, $actualSignature, $secret);
    }

    public function verifyWebhookSignature($payload, $actualSignature, $secret)
    {
        self::verifySignature($payload, $actualSignature, $secret);
    }

    public function verifySignature($payload, $actualSignature, $secret)
    {
        $expectedSignature = hash_hmac(self::SHA256, $payload, $secret);

        // Use lang's built-in hash_equals if exists to mitigate timing attacks
        if (function_exists('hash_equals'))
        {
            $verified = hash_equals($expectedSignature, $actualSignature);
        }
        else
        {
            $verified = $this->hashEquals($expectedSignature, $actualSignature);
        }

        if ($verified === false)
        {
            throw new Errors\SignatureVerificationError(
                'Invalid signature passed');
        }
    }

    private function hashEquals($expectedSignature, $actualSignature)
    {
        if (strlen($expectedSignature) === strlen($actualSignature))
        {
            $res = $expectedSignature ^ $actualSignature;
            $return = 0;

            for ($i = strlen($res) - 1; $i >= 0; $i--)
            {
                $return |= ord($res[$i]);
            }

            return ($return === 0);
        }

        return false;
    }

    public function amountToLowerUnit($amount, $currency): int
    {
        $hooks = new Hooks();

        $request = new Request();

        $hooks->register('curl.before_send', array($request, 'setCurlSslOpts'));

        $options = array(
            'auth' => array(Api::getKey(), Api::getSecret()),
            'hook' => $hooks,
            'timeout' => 60
        );
        
        $headers = [];

        $url = 'https://express.razorpay.com/v1/currency-list';

        $response = Requests::request($url, $headers, [], 'GET', $options); 

        if (file_exists(dirname(__FILE__) . '/rzp_currency_list.json') === true)
        {
            if (filemtime(dirname(__FILE__) . '/rzp_currency_list.json') < (time() - 24*60*60))
            {
                file_put_contents(dirname(__FILE__) . '/rzp_currency_list.json', $response->body);
            }
        }
        else
        {
            file_put_contents(dirname(__FILE__) . '/rzp_currency_list.json', $response->body);
        }

        $currencyListJson = file_get_contents(dirname(__FILE__) . '/rzp_currency_list.json');

        $currencyListArray = json_decode($currencyListJson, 1);

        $lowerAmount = null;

        if (array_key_exists($currency, $currencyListArray['currency_list']))
        {
            $exponent = $currencyListArray['currency_list'][$currency]['Exponent'];

            $lowerAmount = (int) round($amount * pow(10, $exponent));
        }

        if (empty($lowerAmount))
        {
            $lowerAmount = 0;
        }

        return $lowerAmount;
    }
}
