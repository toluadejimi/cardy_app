<?php

namespace App\Http\Controllers;

use App\Models\Charge;
use App\Models\EMoney;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Vcard;
use App\Services\Encryption;
use Auth;
use GuzzleHttp\Client;
//use Tymon\JwtAuth\Facades\JwtAuth;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CardController extends Controller
{

    public $SuccessStatus = true;
    public $FailedStatus = false;

    public function get_card_details(Request $request)
    {

        $fund = Charge::where('title', 'funding')->first()->amount;

        $get_rate = Charge::where('title', 'rate')->first();
        $rate = $get_rate->amount;

        $sd = $rate * $fund;

        $min_amount = ($rate * 10) + $sd;
        $max_amount = ($rate * 250) + $sd;

        $get_usd_creation_fee = Charge::where('title', 'usd_card_creation')->first();
        $usd_creation_fee = $get_usd_creation_fee->amount;

        $usd_card_conversion_rate_to_naira = $usd_creation_fee * $rate;

        $users = User::all();

        $check = Vcard::where('user_id', Auth::id())
            ->first();

        if ($check == null) {
            return response()->json([
                'status' => $this->FailedStatus,
                'message' => 'You do not own any card, you need to create a card',
            ], 500);
        }

        $get_id = Vcard::where('user_id', Auth::id())
            ->where('card_type', 'usd')
            ->first();

        $get_status = Vcard::where('user_id', Auth::id())
            ->first()->status;

        $card_type = Vcard::where('user_id', Auth::id())
            ->first()->card_type;

        if ($get_id == null) {

            return response()->json([
                'status' => $this->FailedStatus,
                'message' => "You do not own any card, you need to create a card",
            ], 500);}

        //card transaction
        $id = $get_id->card_id;

        $databody = array();

        $mono_api_key = env('MONO_KEY');

        $body = json_encode($databody);
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, "https://api.withmono.com/issuing/v1/cards/$id/transactions");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_ENCODING, '');
        curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
        curl_setopt($curl, CURLOPT_TIMEOUT, 0);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt(
            $curl,
            CURLOPT_HTTPHEADER,
            array(
                'Content-Type: application/json',
                'Accept: application/json',
                "mono-sec-key: $mono_api_key",

            )
        );

        $var = curl_exec($curl);
        curl_close($curl);

        $var = json_decode($var);

        $cardTransaction = $var->data ?? null;

        $message = $var->message;

        if ($var->status == 'failed') {

            return response()->json([
                'status' => $this->FailedStatus,
                'message' => "Error!! $message",
            ], 500);

        }

        //get_card details
        $id = $get_id->card_id;

        $databody = array();

        $mono_api_key = env('MONO_KEY');

        $body = json_encode($databody);
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, "https://api.withmono.com/issuing/v1/cards/$id");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_ENCODING, '');
        curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
        curl_setopt($curl, CURLOPT_TIMEOUT, 0);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt(
            $curl,
            CURLOPT_HTTPHEADER,
            array(
                'Content-Type: application/json',
                'Accept: application/json',
                "mono-sec-key: $mono_api_key",

            )
        );

        $var = curl_exec($curl);
        curl_close($curl);

        $var = json_decode($var);
        $cardDetails = $var->data;

        if ($get_status == 1) {

            $update = Vcard::where('card_id', $id)
                ->update([
                    'balance' => $var->data->balance,
                    'city' => $var->data->billing_address->city,
                    'country' => $var->data->billing_address->country,
                    'street' => $var->data->billing_address->street,
                    'postal_code' => $var->data->billing_address->postal_code,
                    'state' => $var->data->billing_address->state,
                    'card_status' => $var->data->status,
                    'type' => $var->data->type,
                    'card_id' => $var->data->id,
                    'brand' => $var->data->brand,
                    'status' => 1,
                    'name_on_card' => $var->data->name_on_card,
                    'balance' => $var->data->balance,
                    'created_at' => $var->data->created_at,
                    'card_number' => $var->data->card_number,
                    'cvv' => $var->data->cvv,
                    'expiry_month' => $var->data->expiry_month,
                    'expiry_year' => $var->data->expiry_year,
                    'last_four' => $var->data->last_four,
                    'account_holder' => $var->data->account_holder,
                ]);
        } else {

            $update = Vcard::where('card_id', $id)
                ->update([
                    'city' => $var->data->billing_address->city,
                    'country' => $var->data->billing_address->country,
                    'street' => $var->data->billing_address->street,
                    'postal_code' => $var->data->billing_address->postal_code,
                    'state' => $var->data->billing_address->state,
                    'card_status' => $var->data->status,
                    'type' => $var->data->type,
                    'card_id' => $var->data->id,
                    'brand' => $var->data->brand,
                    'status' => 1,
                    'name_on_card' => $var->data->name_on_card,
                    'balance' => $var->data->balance,
                    'created_at' => $var->data->created_at,
                    'card_number' => $var->data->card_number,
                    'cvv' => $var->data->cvv,
                    'expiry_month' => $var->data->expiry_month,
                    'expiry_year' => $var->data->expiry_year,
                    'last_four' => $var->data->last_four,
                    'account_holder' => $var->data->account_holder,

                ]);
        }

        $carddetails = Vcard::where('card_id', $id)
            ->first();

        $card_amount = number_format($carddetails->balance / 100, 2, '.', '');
        $card_name = $carddetails->name_on_card;
        $city = $carddetails->city;
        $card_status = $carddetails->card_status;
        $country = $carddetails->country;
        $street = $carddetails->street;
        $state = $carddetails->state;
        $zip_code = $carddetails->postal_code;
        $type = $carddetails->type;

        //Decryption of card

        $usd_card_no_decrypt = Encryption::decryptString($carddetails->card_number);
        $usd_card_cvv_decrypt = Encryption::decryptString($carddetails->cvv);
        $usd_card_expiry_month_decrypt = Encryption::decryptString($carddetails->expiry_month);
        $usd_card_expiry_year_decrypt = Encryption::decryptString($carddetails->expiry_year);
        $usd_card_last_decrypt = Encryption::decryptString($carddetails->last_four);

        $card_data = [

            'card_status' => $card_status,
            'card_type' => $card_type,
            'card_amount' => $card_amount,
            'card_no' => $usd_card_no_decrypt,
            'card_cvv' => $usd_card_cvv_decrypt,
            'card_month' => $usd_card_expiry_month_decrypt,
            'card_year' => $usd_card_expiry_year_decrypt,
            'card_last_four' => $usd_card_last_decrypt,

        ];

        $billing_data = [

            'name_on_card' => $card_name,
            'city' => $city,
            'country' => $country,
            'street' => $street,
            'state' => $state,
            'city' => $city,
            'zip_code' => $zip_code,
            'city' => $city,

        ];

        return response()->json([

            'status' => $this->SuccessStatus,
            'card_data' => $card_data,
            'billing_data' => $billing_data,
            'transactions' => $cardTransaction ?? null,

        ], 200);

    }

    public function create_usd_card(Request $request)
    {

        $fund_source = Charge::where('title', 'funding_wallet')
            ->first()->amount;

        $amount_to_fund = $request->amount_to_fund;

        $get_mono_amount_to_fund_in_cent = $amount_to_fund * 100;

        $mono_amount_to_fund_in_cent = round($get_mono_amount_to_fund_in_cent, 2);

        $user_amount = EMoney::where('user_id', Auth::id())
            ->first()->current_balance;

        $get_usd_card_records = Vcard::where('card_type', 'usd')
            ->where('user_id', Auth::id())
            ->get() ?? null;

        if ($amount_to_fund > $user_amount) {
            return response()->json([

                'status' => $this->FailedStatus,
                'message' => 'Error!! Insufficient Funds, Fund your Wallet',

            ], 500);
        }

        if ($mono_amount_to_fund_in_cent < 1000) {
            return response()->json([

                'status' => $this->FailedStatus,
                'message' => 'Error!! Minimum to fund is USD 10',

            ], 500);
        }

        $check_for_usd_virtual_card = Vcard::where('user_id', Auth::id())
            ->where('card_type', 'usd')
            ->first();

        if (empty($check_for_usd_virtual_card)) {

            $api_key = env('ELASTIC_API');
            $from = env('FROM_API');

            $databody = array(
                "account_holder" => Auth::user()->mono_customer_id,
                "currency" => "usd",
                "fund_source" => $fund_source,
                "amount" => $mono_amount_to_fund_in_cent,
            );

            $mono_api_key = env('MONO_KEY');

            $body = json_encode($databody);
            $curl = curl_init();

            curl_setopt($curl, CURLOPT_URL, 'https://api.withmono.com/issuing/v1/cards/virtual');
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_ENCODING, '');
            curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
            curl_setopt($curl, CURLOPT_TIMEOUT, 0);
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt(
                $curl,
                CURLOPT_HTTPHEADER,
                array(
                    'Content-Type: application/json',
                    'Accept: application/json',
                    "mono-sec-key: $mono_api_key",
                )
            );
            // $final_results = curl_exec($curl);

            $var = curl_exec($curl);
            curl_close($curl);

            $var = json_decode($var);

            if ($var->status == 'successful') {

                $card = new Vcard();
                $card->card_id = $var->data->id;
                $card->user_id = Auth::id();
                $card->card_type = 'usd';
                $card->save();

                $debit = $user_amount - $amount_to_fund;
                $update = EMoney::where('user_id', Auth::id())
                    ->update([
                        'current_balance' => $debit,
                    ]);

                $transaction = new Transaction();
                $transaction->ref_trans_id = Str::random(10);
                $transaction->user_id = Auth::id();
                $transaction->transaction_type = "cash_out";
                $transaction->debit = $amount_to_fund;
                $transaction->note = "USD Card Creation and Funding";
                $transaction->save();

                $email = User::where('id', Auth::id())
                    ->first()->email;

                $f_name = User::where('id', Auth::id())
                    ->first()->f_name;

                require_once "vendor/autoload.php";
                $client = new Client([
                    'base_uri' => 'https://api.elasticemail.com',
                ]);

                $res = $client->request('GET', '/v2/email/send', [
                    'query' => [

                        'apikey' => "$api_key",
                        'from' => "$from",
                        'fromName' => 'Cardy',
                        'sender' => "$from",
                        'senderName' => 'Cardy',
                        'subject' => 'Fund Wallet',
                        'to' => "$email",
                        'bodyHtml' => view('card-creation-notification', compact('f_name'))->render(),
                        'encodingType' => 0,

                    ],
                ]);

                $body = $res->getBody();
                $array_body = json_decode($body);

                return response()->json([

                    'status' => $this->SuccessStatus,
                    'message' => 'Card creation is been processed',

                ], 200);

            } else {

                $api_key = env('ELASTIC_API');
                $from = env('FROM_API');

                $err_message = $var->message;

                $client = new Client([
                    'base_uri' => 'https://api.elasticemail.com',
                ]);

                $res = $client->request('GET', '/v2/email/send', [
                    'query' => [

                        'apikey' => "$api_key",
                        'from' => "$from",
                        'fromName' => 'Cardy',
                        'sender' => "$from",
                        'senderName' => 'Cardy',
                        'subject' => 'Card Creation Error',
                        'to' => 'toluadejimi@gmail.com',
                        'bodyText' => "Error from Mono -  $err_message",
                        'encodingType' => 0,

                    ],
                ]);

                $body = $res->getBody();
                $array_body = json_decode($body);

                return response()->json([

                    'status' => $this->FailedStatus,
                    'message' => 'Opps!! Unable to fund card this time, Please Try again Later',

                ], 500);
            }
        }

        return response()->json([

            'status' => $this->FailedStatus,
            'message' => 'Sorry!! You can only have one USD Virtual Card',

        ], 500);

    }

    public function fund_usd_card(Request $request)
    {

        $fund_source = Charge::where('title', 'funding_wallet')
            ->first()->amount;

        $amount_to_fund = $request->amount_to_fund;

        $get_funding_fee = Charge::where('title', 'funding')
            ->first()->amount;

        $rate = Charge::where('title', 'rate')
            ->first()->amount;

        $funding_fee_in_naira = $get_funding_fee * $rate;

        $get_amount_in_naira = (int) $amount_to_fund * (int) $rate;

        $amount_in_naira = (int) $get_amount_in_naira + (int) $funding_fee_in_naira;

        $get_mono_amount_to_fund_in_cent = $amount_to_fund * 100;

        $mono_amount_to_fund_in_cent = round($get_mono_amount_to_fund_in_cent, 2);

        $users_id = Auth::id();

        $card_id = Vcard::where('user_id', Auth::user()->id)
            ->first()->card_id;

        $id = $card_id;

        $user_wallet_banlance = EMoney::where('user_id', Auth::user()->id)
            ->first()->current_balance;

        if ($amount_to_fund <= $user_wallet_banlance) {

            if ($get_mono_amount_to_fund_in_cent >= 1000) {

                //debit user for card funding
                $debit = (int) $user_wallet_banlance - (int) $amount_in_naira;

                $update = EMoney::where('user_id', Auth::id())
                    ->update([
                        'current_balance' => $debit,
                    ]);

                $transaction = new Transaction();
                $transaction->ref_trans_id = Str::random(10);
                $transaction->user_id = Auth::id();
                $transaction->transaction_type = "cash_out";
                $transaction->debit = $amount_in_naira;
                $transaction->note = "Usd Card Funding";
                $transaction->save();

                $mono_api_key = env('MONO_KEY');

                $databody = array(

                    "amount" => $mono_amount_to_fund_in_cent,
                    "fund_source" => $fund_source,
                );

                $body = json_encode($databody);
                $curl = curl_init();

                curl_setopt($curl, CURLOPT_URL, "https://api.withmono.com/issuing/v1/cards/$id/fund");
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl, CURLOPT_ENCODING, '');
                curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
                curl_setopt($curl, CURLOPT_TIMEOUT, 0);
                curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
                curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt(
                    $curl,
                    CURLOPT_HTTPHEADER,
                    array(
                        'Content-Type: application/json',
                        'Accept: application/json',
                        "mono-sec-key: $mono_api_key",
                    )
                );
                // $final_results = curl_exec($curl);

                $var = curl_exec($curl);
                curl_close($curl);

                $var = json_decode($var);

                if ($var->status == 'failed') {

                    $api_key = env('ELASTIC_API');
                    $from = env('FROM_API');

                    $err_message = $var->message;

                    require_once "vendor/autoload.php";
                    $client = new Client([
                        'base_uri' => 'https://api.elasticemail.com',
                    ]);

                    $res = $client->request('GET', '/v2/email/send', [
                        'query' => [

                            'apikey' => "$api_key",
                            'from' => "$from",
                            'fromName' => 'Cardy',
                            'sender' => "$from",
                            'senderName' => 'Cardy',
                            'subject' => 'Card Creation Error',
                            'to' => 'toluadejimi@gmail.com',
                            'bodyText' => "Error from Mono -  $err_message User Id - $users_id Amount - $amount_in_naira ",
                            'encodingType' => 0,

                        ],
                    ]);

                    $body = $res->getBody();
                    $array_body = json_decode($body);

                    return response()->json([

                        'status' => $this->FailedStatus,
                        'message' => 'Sorry!! Unable to fund card, Contact Support',

                    ], 500);
                }

                return response()->json([

                    'status' => $this->SuccessStatus,
                    'message' => "Card Funded with $amount_to_fund",

                ], 200);

            }

            return response()->json([

                'status' => $this->FailedStatus,
                'message' => 'Sorry!! Minimum Amount to fund is 10USD',

            ], 500);
        }

        return response()->json([

            'status' => $this->FailedStatus,
            'message' => 'Sorry!! Insufficient Funds, Fund your Wallet',

        ], 500);

    }

    public function freeze_usd_card(Request $request)
    {

        $mono_api_key = env('MONO_KEY');

        $id = Vcard::where('user_id', Auth::id())
            ->first()->card_id;

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, "https://api.withmono.com/issuing/v1/cards/$id/freeze");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_ENCODING, '');
        curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
        curl_setopt($curl, CURLOPT_TIMEOUT, 0);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt(
            $curl,
            CURLOPT_HTTPHEADER,
            array(
                'Content-Type: application/json',
                'Accept: application/json',
                "mono-sec-key: $mono_api_key",
            )
        );
        // $final_results = curl_exec($curl);

        $var = curl_exec($curl);
        curl_close($curl);

        $var = json_decode($var);

        $err_message = $var->message;



        if($var->status == 'successful'){

            return response()->json([

                'status' => $this->SuccessStatus,
                'message' => "Card has been successfully freezed",

            ], 200);


        }

        return response()->json([

            'status' => $this->FailedStatus,
            'message' => "Sorry!! $err_message",

        ], 500);


    }


    public function unfreeze_usd_card(Request $request)
    {

        $mono_api_key = env('MONO_KEY');

        $id = Vcard::where('user_id', Auth::id())
            ->first()->card_id;

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, "https://api.withmono.com/issuing/v1/cards/$id/unfreeze");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_ENCODING, '');
        curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
        curl_setopt($curl, CURLOPT_TIMEOUT, 0);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt(
            $curl,
            CURLOPT_HTTPHEADER,
            array(
                'Content-Type: application/json',
                'Accept: application/json',
                "mono-sec-key: $mono_api_key",
            )
        );
        // $final_results = curl_exec($curl);

        $var = curl_exec($curl);
        curl_close($curl);

        $var = json_decode($var);

        $err_message = $var->message;



        if($var->status == 'successful'){

            return response()->json([

                'status' => $this->SuccessStatus,
                'message' => "Card has been successfully unfreezed",

            ], 200);


        }

        return response()->json([

            'status' => $this->FailedStatus,
            'message' => "Sorry!! $err_message",

        ], 500);


    }

}
