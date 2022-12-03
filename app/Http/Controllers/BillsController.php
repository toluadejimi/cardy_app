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
use PhpParser\Node\Expr\AssignOp\Pow;

class BillsController extends Controller
{
    //
    public $successStatus = true;
    public $failedStatus = false;
    public $auth = "dG9sdWFkZWppbWlAZ21haWwuY29tOlRvbHVsb3BlMjU4MEA";



    public function buy_airtime_for_self(Request $request)
    {

        $api_key = env('ELASTIC_API');
        $from = env('FROM_API');

        $auth = $this->auth;



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

        $auth = $this->auth;



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


    public function data_type(){


        $client = new \GuzzleHttp\Client();
        $request = $client->get('https://vtpass.com/api/service-variations?serviceID=mtn-data');
        $response = $request->getBody();
        $result = json_decode($response);
        $get_mtn_network = $result->content->variations;

        $client = new \GuzzleHttp\Client();
        $request = $client->get('https://vtpass.com/api/service-variations?serviceID=glo-data');
        $response = $request->getBody();
        $result = json_decode($response);
        $get_glo_network = $result->content->variations;

        $client = new \GuzzleHttp\Client();
        $request = $client->get('https://vtpass.com/api/service-variations?serviceID=airtel-data');
        $response = $request->getBody();
        $result = json_decode($response);
        $get_airtel_network = $result->content->variations;

        $client = new \GuzzleHttp\Client();
        $request = $client->get('https://vtpass.com/api/service-variations?serviceID=etisalat-data');
        $response = $request->getBody();
        $result = json_decode($response);
        $get_9mobile_network = $result->content->variations;

        $client = new \GuzzleHttp\Client();
        $request = $client->get('https://vtpass.com/api/service-variations?serviceID=smile-direct');
        $response = $request->getBody();
        $result = json_decode($response);
        $get_smile_network = $result->content->variations;

        $client = new \GuzzleHttp\Client();
        $request = $client->get('https://vtpass.com/api/service-variations?serviceID=spectranet');
        $response = $request->getBody();
        $result = json_decode($response);
        $get_spectranet_network = $result->content->variations;


        return response()->json([

            'status' => $this->successStatus,
            'mtn_data' => $get_mtn_network,
            'glo_data' => $get_glo_network,
            'airtel_data' => $get_airtel_network,
            '9mobile_data' => $get_9mobile_network,
            'smile_data' =>  $get_smile_network,
            'spectranet_data' => $get_spectranet_network,

        ],200);





    }


    public function buy_data(Request $request)
    {

        $api_key = env('ELASTIC_API');
        $from = env('FROM_API');

        $auth = $this->auth;

        $request_id = date('YmdHis') . Str::random(4);

        $serviceid = $request->service_id;

        $biller_code = $request->phone;

        //$phone = preg_replace('/[^0-9]/', '', $request->biller_code);

        $variation_code = $request->variation_code;

        //preg_match_all('!\d+!', $variation_code, $matches);

        $amount = $request->amount;

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
                'variation_code' => $variation_code,
                'serviceID' => $serviceid,
                'amount' => $amount,
                'biller_code' => $biller_code,
                'phone' => $biller_code,
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
            $transaction->note = "Data Purchase to $biller_code";
            $transaction->save();

            $email = User::where('id', Auth::id())
                ->first()->email;

            $f_name = User::where('id', Auth::id())
                ->first()->f_name;

            $client = new Client([
                'base_uri' => 'https://api.elasticemail.com',
            ]);

            $phone = $biller_code;

            $res = $client->request('GET', '/v2/email/send', [
                'query' => [

                    'apikey' => "$api_key",
                    'from' => "$from",
                    'fromName' => 'Cardy',
                    'sender' => "$from",
                    'senderName' => 'Cardy',
                    'subject' => 'Data Purchase',
                    'to' => "$email",
                    'bodyHtml' => view('airtime-notification', compact('f_name', 'amount', 'phone'))->render(),
                    'encodingType' => 0,

                ],
            ]);

            $body = $res->getBody();
            $array_body = json_decode($body);

            return response()->json([

                'status' => $this->successStatus,
                'message' => 'Data Purchase Successful'


            ],200);


        } return response()->json([

            'status' => $this->failedStatus,
            'message' => "Failed!! Please try again later"


        ],500);
    }



    public function verify(Request $request)
    {




        $auth = $this->auth;

        $billersCode = $request->billers_code;
        $serviceID = $request->service_id;
        $type = $request->type;



        if($serviceID == 'gotv'){


            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://vtpass.com/api/merchant-verify',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => array(
                    'billersCode' => $billersCode,
                    'serviceID' => $serviceID,
                ),
                CURLOPT_HTTPHEADER => array(
                    "Authorization: Basic $auth=",
                    'Cookie: laravel_session=eyJpdiI6IlBkTGc5emRPMmhyQVwvb096YkVKV2RnPT0iLCJ2YWx1ZSI6IkNvSytPVTV5TW52K2tBRlp1R2pqaUpnRDk5YnFRbEhuTHhaNktFcnBhMFRHTlNzRWIrejJxT05kM1wvM1hEYktPT2JKT2dJWHQzdFVaYnZrRytwZ2NmQT09IiwibWFjIjoiZWM5ZjI3NzBmZTBmOTZmZDg3ZTUxMDBjODYxMzQ3OTkxN2M4YTAxNjNmMWY2YjAxZTIzNmNmNWNhOWExNzJmOCJ9',
                ),
            ));

            $var = curl_exec($curl);
            curl_close($curl);

            $var = json_decode($var);

            if ($var->code == 000) {

                $customer_name = $var->content->Customer_Name;
                $plan = $var->content->Current_Bouquet;

                $update = User::where('id', Auth::id())
                    ->update([
                        'gotv_number' => $billers_code,
                        'current_gotv_plan' => $plan,
                    ]);

                    return response()->json([

                        'status' => $this->successStatus,
                        'data' => "$customer_name",
                        'plan' => "$plan"

                    ],200);


        }

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://vtpass.com/api/merchant-verify',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => array(
                'billersCode' => $billersCode,
                'serviceID' => $serviceID,
                'type' => $type,
            ),
            CURLOPT_HTTPHEADER => array(
                "Authorization: Basic $auth=",
                'Cookie: laravel_session=eyJpdiI6IlBkTGc5emRPMmhyQVwvb096YkVKV2RnPT0iLCJ2YWx1ZSI6IkNvSytPVTV5TW52K2tBRlp1R2pqaUpnRDk5YnFRbEhuTHhaNktFcnBhMFRHTlNzRWIrejJxT05kM1wvM1hEYktPT2JKT2dJWHQzdFVaYnZrRytwZ2NmQT09IiwibWFjIjoiZWM5ZjI3NzBmZTBmOTZmZDg3ZTUxMDBjODYxMzQ3OTkxN2M4YTAxNjNmMWY2YjAxZTIzNmNmNWNhOWExNzJmOCJ9',
            ),
        ));

        $var = curl_exec($curl);
        curl_close($curl);

        $var = json_decode($var);

        $status = $var->content->WrongBillersCode;

        if ($status == true) {

            return response()->json([

                'status' => $this->failedStatus,
                'message' => "Please check the Meter No and try again"

            ],500);

        }

        if ($var->code == 000) {

            $customer_name = $var->content->Customer_Name;
            $eletric_address = $var->content->Address;
            $meter_no = $var->content->Meter_Number;

            $update = User::where('id', Auth::id())
                ->update([
                    'meter_number' => $meter_no,
                    'eletric_company' => $serviceID,
                    'eletric_type' => $type,
                    'eletric_address' => $eletric_address,

                ]);

                return response()->json([

                    'status' => $this->successStatus,
                    'data' => "$customer_name"

                ],200);

            }
        }

    }

    public function get_token_company(){

    $token_company = Power::all();

    return response()->json([

        'status' => $this->successStatus,
        'data' => $token_company,

    ],200);



    }




}
