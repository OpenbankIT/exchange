<?php

use Phalcon\DI;

use \Smartmoney\Stellar\Payment;

class IndexTask extends TaskBase
{
    public function bitcoinAction()
    {
        $bitcoind = new \App\Lib\jsonRPCClient(
            "http://" . $this->config->exchange->bitcoind->user . ":" . $this->config->exchange->bitcoind->pass . "@"
            . $this->config->exchange->bitcoind->host . ":" . $this->config->exchange->bitcoind->port . "/"
        );

        $btc_need_verify = \App\Collections\Cashin::find(
            [
                [
                    'type' => \App\Collections\Cashin::TYPE_BTC,
                    'status' => [
                        '$in' => [
                            \App\Collections\Cashin::STATUS_WAITING,
                            \App\Collections\Cashin::STATUS_PAID,

                        ]
                    ]
                ]
            ]
        );

        foreach ($btc_need_verify as $order_info) {

            if (empty($order_info->to)) {
                continue;
            }

            $received = $bitcoind->getreceivedbyaddress($order_info->to, $this->config->exchange->bitcoind->count_of_confirmations);

            $mask = $this->config->exchange->bitcoind->decimal_mask;

            if (sprintf($mask, $received) > 0) {

                if (sprintf($mask, $received) >= sprintf($mask, $order_info->cost)) {

                    if ($order_info->status == \App\Collections\Cashin::STATUS_WAITING) {
                        $order_info->status = \App\Collections\Cashin::STATUS_PAID;
                    }

                    $order_info->get = $received;

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
                            }

                        }

                    }

                } else {
                    $order_info->status = \App\Collections\Cashin::STATUS_PARTIAL_PAYMENT;
                    $order_info->get = $received;
                    $order_info->save();
                }

            }

        }

    }

    public function getratesAction(){

        $bitcoin_rate = \App\Collections\Rates::findFirst(
            [
                [
                    'from'  => 'BTC',
                    'to'    => 'UAH'
                ]
            ]
        );

        if (empty($bitcoin_rate)) {

            $data = json_decode(file_get_contents($this->config->exchange->bitcoind->rate_url));

            if (!empty($data)) {

                //get usd-uah middle rate value
                $usd_uah_rate = 0;
                foreach ($data as $item) {
                    if($item->ccy == 'USD' && $item->base_ccy == 'UAH') {
                        $usd_uah_rate = ($item->buy + $item->sale) / 2;
                    }
                }

                $btc_uah_rate = 0;
                if (!empty($usd_uah_rate)) {

                    //get btc-uah middle rate value
                    foreach ($data as $item) {
                        if($item->ccy == 'BTC' && $item->base_ccy == 'USD') {
                            $btc_uah_rate = (($item->buy + $item->sale) / 2) * $usd_uah_rate;
                        }
                    }
                }

                if (!empty($btc_uah_rate)) {

                    $btc_to_uah             = new \App\Collections\Rates();

                    $btc_to_uah->from       = 'BTC';
                    $btc_to_uah->to         = 'UAH';
                    $btc_to_uah->rate       = $btc_uah_rate;
                    $btc_to_uah->created    = new \MongoDate(time());

                    $btc_to_uah->save();

                }

            }
        }
    }

}