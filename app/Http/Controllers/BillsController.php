<?php

namespace App\Http\Controllers;

use App\Http\Traits\HistoryTrait;
use App\Models\Bank;
use App\Models\BankTransfer;
use App\Models\Charge;
use App\Models\DataType;
use App\Models\EMoney;
use App\Models\Power;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Encryption;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use App\Mail\UsdCardEmail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Mail;


class BillsController extends Controller
{
    //
    public $successStatus = true;
    public $failedStatus = false;


    public function buy_airtime_for_self(Request $request)
    {

        $api_key = env('ELASTIC_API');
        $from = env('FROM_API');

        $auth = env('VTAUTH');

        $request_id = date('YmdHis') . Str::random(4);

        $serviceid = $request->service_id;

        $amount = $request->amount;

        $phone = Auth::user()->phone;

        $transfer_pin = $request->pin;

        $user_wallet_banlance = EMoney::where('user_id', Auth::user()->id)
            ->first()->current_balance;

        $getpin = Auth()->user();
        $user_pin = $getpin->pin;

        if (Hash::check($transfer_pin, $user_pin) == false) {
            return response()->json([
                'status' => $this->failedStatus,
                'message' => "Failed!! Invalid Pin"
            ],500);

        }

        if ($amount < 100) {
            return response()->json([

                'status' => $this->failedStatus,
                'message' => "Failed!! Amount must not be less than NGN 100"


            ],500);
        }

        if ($amount > $user_wallet_banlance) {

            return response()->json([

                'status' => $this->failedStatus,
                'message' => "Failed!! Insufficient Funds, Fund your wallet"


            ],500);

        }

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://vtpass.com/api/pay',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => array(
                'request_id' => $request_id,
                'serviceID' => $serviceid,
                'amount' => $amount,
                'phone' => $phone,
            ),
            CURLOPT_HTTPHEADER => array(
                "Authorization: Basic $auth=",
                'Cookie: laravel_session=eyJpdiI6IlBkTGc5emRPMmhyQVwvb096YkVKV2RnPT0iLCJ2YWx1ZSI6IkNvSytPVTV5TW52K2tBRlp1R2pqaUpnRDk5YnFRbEhuTHhaNktFcnBhMFRHTlNzRWIrejJxT05kM1wvM1hEYktPT2JKT2dJWHQzdFVaYnZrRytwZ2NmQT09IiwibWFjIjoiZWM5ZjI3NzBmZTBmOTZmZDg3ZTUxMDBjODYxMzQ3OTkxN2M4YTAxNjNmMWY2YjAxZTIzNmNmNWNhOWExNzJmOCJ9',
            ),
        ));

        $var = curl_exec($curl);
        curl_close($curl);

        $var = json_decode($var);

        dd($var , $auth );

        $trx_id = $var->requestId;

        if ($var->response_description == 'TRANSACTION SUCCESSFUL') {

            $user_amount = EMoney::where('user_id', Auth::id())
                ->first()->current_balance;

            $debit = $user_amount - $amount;
            $update = EMoney::where('user_id', Auth::id())
                ->update([
                    'current_balance' => $debit,
                ]);

            $transaction = new Transaction();
            $transaction->ref_trans_id = Str::random(10);
            $transaction->user_id = Auth::id();
            $transaction->transaction_type = "cash_out";
            $transaction->type = "vas";
            $transaction->debit = $amount;
            $transaction->note = "Airtime Purchase to $phone";
            $transaction->save();

            $email = User::where('id', Auth::id())
                ->first()->email;

            $f_name = User::where('id', Auth::id())
                ->first()->f_name;

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
                    'subject' => 'Airtime VTU Purchase',
                    'to' => "$email",
                    'bodyHtml' => view('airtime-notification', compact('f_name', 'amount', 'phone'))->render(),
                    'encodingType' => 0,

                ],
            ]);

            $body = $res->getBody();
            $array_body = json_decode($body);

            return response()->json([

                'status' => $this->successStatus,
                'message' => 'Airtime Purchase Successful'


            ],200);


        } return response()->json([

            'status' => $this->failedStatus,
            'message' => "Failed!! Please try again later"


        ],500);

    }

    public function buy_airtime_for_others(Request $request)
    {

        $api_key = env('ELASTIC_API');
        $from = env('FROM_API');

        $auth = env('VTAUTH');

        $request_id = date('YmdHis') . Str::random(4);

        $serviceid = $request->service_id;

        $amount = $request->amount;

        $phone = $request->phone;

        $transfer_pin = $request->pin;

        $user_wallet_banlance = EMoney::where('user_id', Auth::user()->id)
            ->first()->current_balance;

        $getpin = Auth()->user();
        $user_pin = $getpin->pin;

        if (Hash::check($transfer_pin, $user_pin) == false) {
            return back()->with('error', 'Invalid Pin');
        }

        if ($amount < 100) {
            return back()->with('error', 'Amount must not be less than NGN 100');
        }

        if ($amount > $user_wallet_banlance) {

            return back()->with('error', 'Insufficient Funds, Fund your wallet');

        }

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://vtpass.com/api/pay',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => array(
                'request_id' => $request_id,
                'serviceID' => $serviceid,
                'amount' => $amount,
                'phone' => $phone,
            ),
            CURLOPT_HTTPHEADER => array(
                "Authorization: Basic $auth=",
                'Cookie: laravel_session=eyJpdiI6IlBkTGc5emRPMmhyQVwvb096YkVKV2RnPT0iLCJ2YWx1ZSI6IkNvSytPVTV5TW52K2tBRlp1R2pqaUpnRDk5YnFRbEhuTHhaNktFcnBhMFRHTlNzRWIrejJxT05kM1wvM1hEYktPT2JKT2dJWHQzdFVaYnZrRytwZ2NmQT09IiwibWFjIjoiZWM5ZjI3NzBmZTBmOTZmZDg3ZTUxMDBjODYxMzQ3OTkxN2M4YTAxNjNmMWY2YjAxZTIzNmNmNWNhOWExNzJmOCJ9',
            ),
        ));

        $var = curl_exec($curl);
        curl_close($curl);

        $var = json_decode($var);

        $trx_id = $var->requestId;

        if ($var->response_description == 'TRANSACTION SUCCESSFUL') {

            $user_amount = EMoney::where('user_id', Auth::id())
                ->first()->current_balance;

            $debit = $user_amount - $amount;
            $update = EMoney::where('user_id', Auth::id())
                ->update([
                    'current_balance' => $debit,
                ]);

            $transaction = new Transaction();
            $transaction->ref_trans_id = Str::random(10);
            $transaction->user_id = Auth::id();
            $transaction->transaction_type = "cash_out";
            $transaction->type = "vas";
            $transaction->debit = $amount;
            $transaction->note = "Airtime Purchase to $phone";
            $transaction->save();

            $email = User::where('id', Auth::id())
                ->first()->email;

            $f_name = User::where('id', Auth::id())
                ->first()->f_name;

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
                    'subject' => 'Airtime VTU Purchase',
                    'to' => "$email",
                    'bodyHtml' => view('airtime-notification', compact('f_name', 'amount', 'phone'))->render(),
                    'encodingType' => 0,

                ],
            ]);

            $body = $res->getBody();
            $array_body = json_decode($body);

            return back()->with('message', 'Airtime Purchase Successfull');

        }return back()->with('error', "Failed!! Please try again later");

    }





}
