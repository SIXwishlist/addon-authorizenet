<?php
namespace Addon\Authorizenet\Libraries\Api;

use App\Libraries\Interfaces\Gateway\MerchantCc;

class AuthorizenetCimApi
{

    public $cmd;

    private $live_api = 'https://api.authorize.net/xml/v1/request.api';
    private $sandbox_api = 'https://apitest.authorize.net/xml/v1/request.api';

    private $_xml;
    private $_refId = false;
    private $_validationMode = "none"; // "none","testMode","liveMode"
    private $_extraOptions;
    private $_transactionTypes = array(
        'AuthOnly',
        'AuthCapture',
        'CaptureOnly',
        'PriorAuthCapture',
        'Refund',
        'Void',
    );

    public function __construct($login_id, $transaction_key, $api_key)
    {

    }

}
