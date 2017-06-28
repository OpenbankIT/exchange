<?php

namespace App\Frontend\Controllers;

use \Smartmoney\Stellar\Account;
use \Smartmoney\Stellar\Payment;


class CashinController extends ControllerBase
{
    public function btcAction()
    {

        $errors = [];

        $bitcoin_rate = \App\Collections\Rates::findFirst(
            [
                [
                    'from'  => 'BTC',
                    'to'    => 'UAH'
                ]
            ]
        );

        if (empty($bitcoin_rate->rate)) {
            $errors[] = $this->_locale->_('Can not find exchange rate for this pair');
            return $this->view->errors = $errors;

        }

        $bitcoin_rate = $bitcoin_rate->rate;

        if ($this->request->isPost()) {

            $amount = (double)$this->request->getPost('amount');
            $account = trim(mb_strtoupper((string)$this->request->getPost('account')));
            $asset = trim($this->request->getPost('asset'));

            if (!empty($amount) && !empty($account) && !empty($asset)) {

                if (Account::isValidAccountId($account)) {

                    $account_type = Account::getAccountType($account, $this->config->horizon->host,
                        $this->config->horizon->port);

                    if (in_array($account_type, (array)$this->config->horizon->valid_account_types)) {

                        $mask = $this->config->exchange->bitcoind->decimal_mask;

                        $amount_in_bitcoin = (float)sprintf($mask, $amount / $bitcoin_rate);

                        $cashin_collection = $this->createNewOrder($account, $amount, $asset,
                            $amount_in_bitcoin, \App\Collections\Cashin::STATUS_WAITING, \App\Collections\Cashin::TYPE_BTC);

                        if ($cashin_collection->save()) {
                            $id = $cashin_collection->_id->{'$id'};
                        }

                        if (!empty($id)) {

                            $bitcoind = new \App\Lib\jsonRPCClient(
                                "http://" . $this->config->exchange->bitcoind->user . ":" . $this->config->exchange->bitcoind->pass . "@"
                                . $this->config->exchange->bitcoind->host . ":" . $this->config->exchange->bitcoind->port . "/"
                            );

                            $new_address = $bitcoind->getnewaddress();

                            if (empty($new_address)) {
                                throw new \Exception('Can not get new address from bitcoind');
                            }

                            $cashin_collection->to = $new_address;

                            //build payment link
                            //format of link:       bitcoin:<address>[?amount=<amount>][?label=<label>][?message=<message>]

                            $linkForBitcoin = 'bitcoin:' . $new_address . '?';

                            $params = [
                                'amount' => $amount_in_bitcoin,
                                'message' => 'Buy ' . $amount . ' ' . $asset . ' for ' . $amount_in_bitcoin . ' bitcoin',
                            ];

                            $linkForBitcoin .= http_build_query($params);

                            //build qrcode
                            ob_start();
                            \PHPQRCode\QRcode::png($linkForBitcoin, null);
                            $qrcode = base64_encode( ob_get_contents() );
                            ob_end_clean();

                            if ($cashin_collection->save()) {
                                $this->view->qrcode         = $qrcode;
                                $this->view->address        = $new_address;
                                $this->view->amount_in_btc  = $amount_in_bitcoin;
                            } else {
                                $errors[] = $this->_locale->_('Can not save to db');
                            }

                        }
                    } else {
                        $errors[] = $this->_locale->_('Invalid account type');
                    }
                } else {
                    $errors[] = $this->_locale->_('Invalid account');
                }
            } else {
                $errors[] = $this->_locale->_('At least one parameter is empty');
            }
        }

        if (count($errors)) {
            $this->view->errors = $errors;
        }

        $this->view->account = !empty($this->_session->acc) ? $this->_session->acc : '';
        $this->view->assets = $this->config->assets;

    }

    public function privat24Action()
    {
        $errors = [];
        $messages = [];
        //success info from privar send for this url with get param "result"
        if (!empty($this->request->get('result'))) {

            if ($this->request->isPost()) {

                $params = $this->request->getPost();

                $payment = !empty($params['payment']) ? $params['payment'] : null;
                $post_signature = !empty($params['signature']) ? $params['signature'] : null;

                parse_str($payment, $payment_details);

                $payment_order_info = !empty($payment_details['order']) ? $payment_details['order'] : null;
                $merchant = !empty($payment_details['merchant']) ? $payment_details['merchant'] : null;

                if ($post_signature && $payment_order_info && $merchant == $this->config->exchange->privat24->merchant) {

                    $real_signature = sha1(md5($payment . $this->config->exchange->privat24->secret_key));

                    if ($real_signature == $post_signature) {

                        $success_message = $this->_locale->_('Success payment') . '. ';

                        if (!empty($payment_details['details'])) {
                            $success_message .= $this->_locale->_('Payment details') . $payment_details['details'];
                        }

                        $messages[] = $success_message;

                    } else {
                        $errors[] = $this->_locale->_('Invalid payment signature');
                    }
                }
            } else {
                $errors[] = $this->_locale->_('Fail payment');
            }
        }

        if ($this->request->isPost()) {
            $amount  = (double)$this->request->getPost('amount');
            $account = trim(mb_strtoupper((string)$this->request->getPost('account')));
            $asset   = (string)$this->request->getPost('asset');

            if (!empty($amount) && !empty($account) && !empty($asset)) {

                if (Account::isValidAccountId($account)) {

                    $account_type = Account::getAccountType($account,
                        $this->config->horizon->host, $this->config->horizon->port);

                    if (in_array($account_type, (array)$this->config->horizon->valid_account_types)) {

                        $cashin_collection = $this->createNewOrder($account, $amount, $asset,
                            $amount, \App\Collections\Cashin::STATUS_WAITING, \App\Collections\Cashin::TYPE_PRIVAT24);

                        if ($cashin_collection->save()) {
                            $id = $cashin_collection->_id->{'$id'};
                        }

                        if (!empty($id)) {

                            $server_url = '/cashin/responseprivat24';

                            $params = [
                                'amt' => number_format($amount, 2, '.', ''),
                                'ccy' => 'UAH',
                                'merchant' => $this->config->exchange->privat24->merchant,
                                'order' => $id,
                                'details' => 'Buy ' . $amount . ' ' . $asset . ' for ' . $amount . ' UAH',
                                'ext_details' => '',
                                'pay_way' => 'privat24',
                                'return_url' => $this->request->getScheme() . '://' . $this->request->getHttpHost() . strtok($this->request->getURI(),
                                        '?') . '?result=true',
                                'server_url' => $this->request->getScheme() . '://' . $this->request->getHttpHost() . $server_url,
                            ];

                            $this->view->amount     = $amount;
                            $this->view->asset      = $asset;
                            $this->view->form_data  = $params;
                            $this->view->send_url   = $this->config->exchange->privat24->service_url;
                        }
                    } else {
                        $errors[] = $this->_locale->_('Invalid account type');
                    }
                } else {
                    $errors[] = $this->_locale->_('Invalid account');
                }
            }
        }


        if (count($errors)) {
            $this->view->errors = $errors;
        }

        if (count($messages)) {
            $this->view->messages = $messages;
        }

        $this->view->account = !empty($this->_session->acc) ? $this->_session->acc : '';
        $this->view->assets = $this->config->assets;

    }

    public function responseprivat24Action()
    {

        $this->view->disable();

        if ($this->request->isPost()) {

            $params = $this->request->getPost();

            $payment = !empty($params['payment']) ? $params['payment'] : null;
            $post_signature = !empty($params['signature']) ? $params['signature'] : null;

            parse_str($payment, $payment_details);

            $payment_order_id = !empty($payment_details['order']) ? $payment_details['order'] : null;
            $merchant = !empty($payment_details['merchant']) ? $payment_details['merchant'] : null;

            if ($post_signature && $payment_order_id && $merchant == $this->config->exchange->privat24->merchant) {

                if ($payment_order_id) {

                    $order_info = \App\Collections\Cashin::findById($payment_order_id);

                    if (!empty($order_info)) {

                        if (!isset($order_info->status)) {
                            throw new \Exception('Invoice has bad status');
                        }

                        if ($order_info->status != \App\Collections\Cashin::STATUS_WAITING) {
                            throw new \Exception('Invoice already handled');
                        }

                        $real_signature = sha1(md5($payment . $this->config->exchange->privat24->secret_key));

                        if ($real_signature == $post_signature) {

                            if ($payment_details['amt'] == $order_info->cost) {

                                $order_info->status = \App\Collections\Cashin::STATUS_PAID;

                                if ($order_info->save()) {

                                    //uncomment if in real mode

                                    //if ( empty($payment_details['state']) || $payment_details['state'] != 'test') {

                                        $config = \App\Lib\Tools::buildPaymentConfig($this->config);

                                        $send_result = Payment::sendPaymentByEmission($order_info->client_to, $order_info->amount,
                                            $order_info->asset, $config);

                                        if (!empty($send_result)) {

                                            $decode_send_result = @json_decode($send_result);

                                            if (!empty($decode_send_result->tx_hash)) {
                                                $order_info->status = \App\Collections\Cashin::STATUS_SUCCESS;
                                                $order_info->tx     = $decode_send_result->tx_hash;
                                                $order_info->save();
                                            } else {
                                                $order_info->status = \App\Collections\Cashin::STATUS_FAIL;

                                                if (!empty($decode_send_result->request_error)) {
                                                    $order_info->fail_description = 'Request error. Response (base64): ' .
                                                        $decode_send_result->request_error;
                                                } else {
                                                    $order_info->fail_description = $send_result;
                                                }

                                                $order_info->save();
                                                $this->response->setJsonContent(['error' => $decode_send_result]);
                                                return $this->response->setStatusCode(400)->send();
                                            }

                                        }
                                    //}

                                }

                            }

                        }

                    }

                }

            }

        }

    }

    public function wmuAction()
    {

        $errors = [];
        $messages = [];

        //success/fail info from webmoney send for this url with get param "result"
        if (!empty($this->request->get('result'))) {

            $result_type = $this->request->get('result');

            $params = $this->request->getPost();

            if ($result_type == 'success') {

                if (!empty($params['ORDER_ID'])) {

                    $order_info = \App\Collections\Cashin::findById($params['ORDER_ID']);

                    if (!empty($order_info)) {

                        $success_message = $this->_locale->_('Success payment') . '. ';
                        $messages[] = $success_message;

                    } else {
                        $errors[] = $this->_locale->_('Invalid order id');
                    }
                }
            } else {
                if ($result_type == 'fail') {

                    if (!empty($params['ORDER_ID'])) {

                        $order_info = \App\Collections\Cashin::findById($params['ORDER_ID']);

                        if (!empty($order_info)) {
                            $errors[] = $this->_locale->_('Fail payment');
                        } else {
                            $errors[] = $this->_locale->_('Invalid order id');
                        }
                    }
                }
            }
        }

        if ($this->request->isPost()) {

            $amount = (double)$this->request->getPost('amount');
            $account = trim(mb_strtoupper((string)$this->request->getPost('account')));
            $asset = (string)$this->request->getPost('asset');

            if (!empty($amount) && !empty($account) && !empty($asset)) {

                if (Account::isValidAccountId($account)) {

                    $account_type = Account::getAccountType($account,
                        $this->config->horizon->host,
                        $this->config->horizon->port);


                    if (in_array($account_type, (array)$this->config->horizon->valid_account_types)) {

                        $cashin_collection = $this->createNewOrder($account, $amount, $asset, $amount,
                            \App\Collections\Cashin::STATUS_WAITING, \App\Collections\Cashin::TYPE_WMU);

                        if ($cashin_collection->save()) {
                            $id = $cashin_collection->_id->{'$id'};
                        }

                        if (!empty($id)) {

                            $server_url = '/cashin/responsewmu';

                            $params = [
                                'LMI_PAYMENT_AMOUNT' => number_format($amount, 2, '.', ''),
                                'LMI_PAYEE_PURSE' => $this->config->exchange->wm->wmu,

                                // delete 1 string below in real mode
                                'LMI_SIM_MODE' => 0,

                                'LMI_RESULT_URL' => $this->request->getScheme() . '://' . $this->request->getHttpHost() . $server_url,
                                'LMI_SUCCESS_URL' => $this->request->getScheme() . '://' . $this->request->getHttpHost() . strtok($this->request->getURI(),
                                        '?') . '?result=success',
                                'LMI_SUCCESS_METHOD' => 1, //0 - get, 1 - post, 2 - link
                                'LMI_FAIL_URL' => $this->request->getScheme() . '://' . $this->request->getHttpHost() . strtok($this->request->getURI(),
                                        '?') . '?result=fail',
                                'LMI_FAIL_METHOD' => 1, //0 - get, 1 - post, 2 - link
                                'LMI_PAYMENT_DESC' => 'Buy ' . $amount . ' ' . $asset . ' for ' . $amount . ' UAH',
                                'ORDER_ID' => $id,
                            ];

                            $this->view->amount     = $amount;
                            $this->view->asset      = $asset;
                            $this->view->form_data  = $params;
                            $this->view->send_url   = $this->config->exchange->wm->service_url;
                        }

                    } else {
                        $errors[] = $this->_locale->_('Invalid account type');
                    }
                } else {
                    $errors[] = $this->_locale->_('Invalid account');
                }
            }

        }


        if (count($errors)) {
            $this->view->errors = $errors;
        }

        if (count($messages)) {
            $this->view->messages = $messages;
        }

        $this->view->account = !empty($this->_session->acc) ? $this->_session->acc : '';
        $this->view->assets = $this->config->assets;

    }

    public function responsewmuAction()
    {
        $this->view->disable();

        if ($this->request->isPost()) {

            $params = $this->request->getPost();

            //if payment prerequest
            if (!empty($params['LMI_PREREQUEST'])) {

                //if wallet from answer equal our wallet
                if (!empty($params['LMI_PAYEE_PURSE']) && $params['LMI_PAYEE_PURSE'] == $this->config->exchange->wm->wmu) {

                    $payment_order_id = !empty($params['ORDER_ID']) ? $params['ORDER_ID'] : null;

                    $order_info = \App\Collections\Cashin::findById($payment_order_id);

                    if (!empty($order_info)) {

                        if (!isset($order_info->status)) {
                            throw new \Exception('Invoice has bad status');
                        }

                        if ($order_info->status != \App\Collections\Cashin::STATUS_WAITING) {
                            throw new \Exception('Invoice already handled');
                        }

                        if (empty($params['LMI_PAYMENT_AMOUNT'])) {
                            throw new \Exception('Empty amount in response');
                        }

                        if ($params['LMI_PAYMENT_AMOUNT'] == $order_info->amount) {

                            // LMI_MODE = 1 - test mode, 0 - real mode
                            if (!empty($params['LMI_MODE'])) { //change to empty($params['LMI_MODE'] in real mode!!!

                                if (empty($params['LMI_PAYER_PURSE'])) {
                                    throw new \Exception('Empty payer wallet in response');
                                }

                                $order_info->from = $params['LMI_PAYER_PURSE'];
                                $order_info->to = $params['LMI_PAYEE_PURSE'];

                                if ($order_info->save()) {
                                    echo 'YES';
                                    die;
                                }

                            }

                        }
                    }
                }

            } else {
                //if payment request

                if (!empty($params['LMI_PAYEE_PURSE']) && $params['LMI_PAYEE_PURSE'] == $this->config->exchange->wm->wmu) {

                    $payment_order_id = $params['ORDER_ID'] ? $params['ORDER_ID'] : null;

                    $order_info = \App\Collections\Cashin::findById($payment_order_id);

                    if (!empty($order_info)) {

                        if (!isset($order_info->status)) {
                            throw new \Exception('Invoice has bad status');
                        }

                        if ($order_info->status != \App\Collections\Cashin::STATUS_WAITING) {
                            throw new \Exception('Invoice already handled');
                        }

                        if (empty($params['LMI_PAYMENT_AMOUNT'])) {
                            throw new \Exception('Empty amount in response');
                        }

                        if ($params['LMI_PAYMENT_AMOUNT'] == $order_info->amount) {

                            if (empty($params['LMI_PAYER_PURSE'])) {
                                throw new \Exception('Empty payer wallet in response');
                            }

                            if ($order_info->from != $params['LMI_PAYER_PURSE']) {
                                throw new \Exception('Prerequest payer wallet not equal with current');
                            }

                            if ($order_info->to != $params['LMI_PAYEE_PURSE']) {
                                throw new \Exception('Prerequest payee wallet not equal with current');
                            }

                            if (empty($params['LMI_HASH'])) {
                                throw new \Exception('Empty hash in response');
                            }

                            // LMI_MODE = 1 - test mode, 0 - real mode
                            if (!empty($params['LMI_MODE'])) { //change to empty($params['LMI_MODE'] in real mode!!!

                                $hash_data = $params['LMI_PAYEE_PURSE']
                                    . $params['LMI_PAYMENT_AMOUNT']
                                    . $params['LMI_PAYMENT_NO']
                                    . $params['LMI_MODE']
                                    . $params['LMI_SYS_INVS_NO']
                                    . $params['LMI_SYS_TRANS_NO']
                                    . $params['LMI_SYS_TRANS_DATE']
                                    . $this->config->exchange->wm->secret_key
                                    . $params['LMI_PAYER_PURSE']
                                    . $params['LMI_PAYER_WM'];

                                $hash = mb_strtoupper(hash('sha256', $hash_data));

                                if ($hash != $params['LMI_HASH']) {
                                    throw new \Exception('Invalid hash');
                                }

                                $order_info->status = \App\Collections\Cashin::STATUS_PAID;

                                if ($order_info->save()) {

                                    $config = \App\Lib\Tools::buildPaymentConfig($this->config);

                                    $send_result = Payment::sendPaymentByEmission($order_info->client_to, $order_info->amount,
                                        $order_info->asset, $config);

                                    if (!empty($send_result)) {

                                        $decode_send_result = @json_decode($send_result);

                                        if (!empty($decode_send_result->tx_hash)) {
                                            $order_info->status = \App\Collections\Cashin::STATUS_SUCCESS;
                                            $order_info->tx = $decode_send_result->tx_hash;
                                            $order_info->save();
                                        } else {
                                            $order_info->status = \App\Collections\Cashin::STATUS_FAIL;

                                            if (!empty($decode_send_result->request_error)) {
                                                $order_info->fail_description = 'Request error. Response (base64): ' .
                                                    $decode_send_result->request_error;
                                            } else {
                                                $order_info->fail_description = $send_result;
                                            }

                                            $order_info->save();
                                            $this->response->setJsonContent(['error' => $decode_send_result]);
                                            return $this->response->setStatusCode(400)->send();
                                        }
                                    }
                                } else {
                                    throw new \Exception('Error while saving');
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    private function createNewOrder($account, $amount, $asset, $cost, $status, $type){

        $cashin_collection              = new \App\Collections\Cashin();

        $cashin_collection->status      = $status;
        $cashin_collection->type        = $type;
        $cashin_collection->date        = time();
        $cashin_collection->amount      = $amount;
        $cashin_collection->asset       = $asset;
        $cashin_collection->client_to   = $account;
        $cashin_collection->cost        = $cost;

        return $cashin_collection;

    }

}
