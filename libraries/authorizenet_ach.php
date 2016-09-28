<?php
namespace Addon\Authorizenet\Libraries;

use App\Libraries\Interfaces\Gateway\MerchantAch;

class AuthorizenetAch implements MerchantAch
{
    public $cim;
    public $aim;

    public function initApi()
    {
        require_once 'api/AuthorizeNet.php';

        $login_id = \App::get('configs')->get('settings.authorizenet.authorizenet_cim_login_id');
        $transaction_key = \App::get('configs')->get('settings.authorizenet.authorizenet_cim_transaction_key');
        $sandbox = (\App::get('configs')->get('settings.authorizenet.authorizenet_enable_sandbox') == '1' ? true : false);

        $this->cim = new \AuthorizeNetCIM($login_id, $transaction_key);
        $this->aim = new \AuthorizeNetAIM($login_id, $transaction_key);

        $this->cim->setSandbox($sandbox);
        $this->aim->setSandbox($sandbox);
    }

    public function saveAch($client_id, $data, $ach_id = 0)
    {
        $client = \Client::find($client_id);
        if (empty($client)) {
            return false;
        }

        $this->initApi();

        if ($ach_id > 0) {
            $ach = \ClientAch::find($ach_id);

            $this->cim->deleteCustomerProfile($ach->gateway_data);
        }

        $customer_profile = new \AuthorizeNetCustomer;
        $customer_profile->description = $data['first_name'].' '.$data['last_name'].' '.hash_hmac('sha256', $data['account_routing_number'], \App::get('configs')->get('security.encryption_key'));
        $customer_profile->merchantCustomerId = $client_id;
        $customer_profile->email = $data['email'];

        $payment_profile = new \AuthorizeNetPaymentProfile;
        $payment_profile->customerType = $data['customer_type'];
        $payment_profile->payment->bankAccount->accountType = $data['account_type'];
        $payment_profile->payment->bankAccount->routingNumber = $data['account_routing_number'];
        $payment_profile->payment->bankAccount->accountNumber = $data['account_number'];
        $payment_profile->payment->bankAccount->nameOnAccount = $data['first_name'].' '.$data['last_name'];
        $payment_profile->payment->bankAccount->echeckType = 'WEB';


        $address = new \AuthorizeNetAddress;
        $address->firstName = $data['first_name'];
        $address->lastName = $data['last_name'];
        $address->company = $data['company'];
        $address->address = $data['address1'].' '.$data['address2'];
        $address->city = $data['city'];
        $address->state = $data['state'];
        $address->zip = $data['postcode'];
        $address->country = $data['country'];

        $create_profile = $this->cim->createCustomerProfile($customer_profile);
        $customer_id = $create_profile->getCustomerProfileId();
        $create_shipping_profile = $this->cim->createCustomerShippingAddress($customer_id, $address);
        $address_id = $create_shipping_profile->getCustomerAddressId();
        $create_payment_profile = $this->cim->createCustomerPaymentProfile($customer_id, $payment_profile);

        $customer_profile = $this->cim->getCustomerProfile($customer_id);
        return $customer_id;
    }

    public function deleteAch($data)
    {
        $ach = \ClientAch::find($data['id']);
        $this->initApi();
        $this->cim->deleteCustomerProfile($ach->gateway_data);
    }

    public function attemptInvoiceAchPayment($ach, $invoice)
    {
        $currency = $invoice->Currency()->first();
        $total = $invoice->total - $invoice->total_paid;

        $attempt_payment = $this->attemptAchPayment($ach->id, $currency->id, $total);

        if ($attempt_payment > 0) {
            return true;
        }
        return false;
    }

    public function attemptAchPayment($ach_id = 0, $currency_id, $amount, $data = array(), $client_id = 0)
    {
        $this->initApi();
        $currency = \Currency::find($currency_id);

        if ($ach_id == 0) {
            // This payment is for a non-stored account.

            $customer = new stdClass;
            $customer->first_name = $data['Ach']['first_name'];
            $customer->last_name = $data['Ach']['last_name'];
            $customer->company = $data['Ach']['company'];
            $customer->address = $data['Ach']['address1'].' '.$data['Ach']['address2'];
            $customer->city = $data['Ach']['city'];
            $customer->state = $data['Ach']['state'];
            $customer->country = $data['Ach']['country'];
            $customer->zip = $data['Ach']['postcode'];
            $customer->email = $data['Ach']['email'];
            $customer->cust_id = $client_id;
            $customer->customer_ip = $_SERVER['REMOTE_ADDR'];

            $this->aim->setFields($customer);
            $this->aim->setSandbox(true);
            $this->aim->amount = $amount;

            $this->aim->setECheck(
                $data['Ach']['account_number'],
                $data['Ach']['account_routing_number'],
                $data['Ach']['account_type'],
                '',
                '',
                'WEB'
            );

            $response = $this->aim->authorizeAndCapture();

            return $response->transaction_id;

        } else {
            $ach = \ClientAch::find($ach_id);
            if ($ach->gateway_data !='') {

                $customer_profile = $this->cim->getCustomerProfile($ach->gateway_data);
                $profile = $customer_profile->xml->profile;

                if ($amount > 0 && !empty($currency)) {
                    $transaction = new \AuthorizeNetTransaction;
                    $transaction->amount = $amount;
                    $transaction->customerProfileId = $ach->gateway_data;
                    $transaction->customerShippingAddressId = end($profile->shipToList->customerAddressId);
                    $transaction->customerPaymentProfileId = end($profile->paymentProfiles->customerPaymentProfileId);

                    $item = new \AuthorizeNetLineItem;
                    $item->itemId = "1";
                    $item->name = "add funds";
                    $item->description = "manually add funds";
                    $item->quantity = "1";
                    $item->unitPrice = $amount;
                    $item->taxable = "false";

                    $transaction->lineItems[] = $item;

                    $response = $this->cim->createCustomerProfileTransaction("AuthCapture", $transaction);
                    $transaction_response = $response->getTransactionResponse();
                    $transaction_id = $transaction_response->transaction_id;

                    if ($transaction_id > 0) {
                        return $transaction_id;
                    }

                }
            }
        }
        return false;
    }
}
