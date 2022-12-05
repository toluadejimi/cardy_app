<?php

namespace App\Http\Controllers;

use App\Models\BankTransfer;
use App\Models\EMoney;
use App\Models\Rate;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Bank;
use Auth;
use Exception;
use Hash;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use GuzzleHttp\Client;
use Mail;

class TransactionController extends Controller
{
    //
    public $SuccessStatus = true;
    public $FailedStatus = false;

    public function get_rate(Request $request)
    {
        $rate = Rate::all();

        return response()->json([
            "status" => $this->SuccessStatus,
            "message" => "Successfull",
            "data" => $rate,
        ], 200);

    }

    public function get_all_transactions(Request $request)
    {
        try {
            $user_id = Auth::user()->id;
            $result = Transaction::where('user_id', $user_id)
                ->get();

            return response()->json([
                "status" => $this->SuccessStatus,
                "message" => "Successfull",
                "data" => $result,
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status' => $this->failedStatus,
                'msg' => 'Error',
                'errors' => $e->getMessage(),
            ], 401);
        }

    }

    public function get_banks(Request $request)
    {

        $country = "NG";

        $databody = array(
            "country" => $country,
        );

        $body = json_encode($databody);
        $curl = curl_init();

        $key = env('FLW_SECRET_KEY');
        //"Authorization: $key",
        curl_setopt($curl, CURLOPT_URL, "https://api.flutterwave.com/v3/banks/$country");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_ENCODING, '');
        curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
        curl_setopt($curl, CURLOPT_TIMEOUT, 0);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Accept: application/json',
            "Authorization: $key",
        )
        );

        $var = curl_exec($curl);
        curl_close($curl);

        $var = json_decode($var);
        return response()->json(['status' => $this->SuccessStatus, 'message' => $var], 200);

    }

    public function fetch_account(Request $request)
    {

        $key = env('FLW_SECRET_KEY');

        $account_number = $request->input('account_number');
        $account_bank = $request->input('account_bank');

        $databody = array(
            "account_number" => $account_number,
            "account_bank" => $account_bank,
        );

        $body = json_encode($databody);
        $curl = curl_init();

        $key = env('FLW_SECRET_KEY');
        //"Authorization: $key",
        curl_setopt($curl, CURLOPT_URL, 'https://api.flutterwave.com/v3/accounts/resolve');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_ENCODING, '');
        curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
        curl_setopt($curl, CURLOPT_TIMEOUT, 0);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Accept: application/json',
            "Authorization: $key",
        )
        );

        $var = curl_exec($curl);
        curl_close($curl);

        $var = json_decode($var);
        return response()->json(['status' => $this->SuccessStatus, 'message' => $var], 200);

    }

    public function verify_pin(Request $request)
    {

        $transfer_pin = $request->input('transfer_pin');

        $getpin = Auth()->user();
        $user_pin = $getpin->pin;

        if (Hash::check($transfer_pin, $user_pin)) {

            return response()->json([
                "status" => $this->SuccessStatus,
                "message" => "Pin Confrimed",
            ], 200);
        } else {
            return response()->json([
                "status" => $this->FailedStatus,
                "message" => "Incorrect Pin, Please try again",
            ], 500);
        }

    }

    public function bank_transfer(Request $request)
    {

        $key = env('FLW_SECRET_KEY');

        $user_id = Auth::user()->id;
        $account_number = Auth::user()->account_number;
        $account_bank = Auth::user()->bank_code;
        $amount = $request->amount;
        $narration = "Debit";
        $currency = "NGN";

        $user_wallet = Auth::user()->wallet;

        if ($user_wallet >= $amount) {

            //update wallet
            $userwallet = Auth()->user();
            $useramount = $userwallet->wallet;
            $removemoney = (int) $useramount - (int) $amount;

            $update = User::where('id', $user_id)
                ->update(['wallet' => $removemoney]);

            $databody = array(
                "account_number" => $account_number,
                "account_bank" => $account_bank,
                "amount" => $amount,
                "amount" => $amount,
                "narration" => $narration,
                "currency" => $currency,

            );

            $body = json_encode($databody);
            $curl = curl_init();

            $key = env('FLW_SECRET_KEY');
            //"Authorization: $key",
            curl_setopt($curl, CURLOPT_URL, 'https://api.flutterwave.com/v3/transfers');
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_ENCODING, '');
            curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
            curl_setopt($curl, CURLOPT_TIMEOUT, 0);
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Accept: application/json',
                "Authorization: $key",
            )
            );

            $var = curl_exec($curl);
            curl_close($curl);

            $var = json_decode($var);

            if ($var->status == "success") {

                //create Debit transaction
                $transaction = new Transaction();
                $transaction->user_id = $user_id;
                $transaction->reference = $var->data->reference;
                $transaction->amount = $amount;
                $transaction->type = 'Debit';
                $transaction->trans_id = $var->data->id;
                $transaction->save();

                $receiveremail = Auth::user()->email;

                //send email
                $data = array(
                    'fromsender' => 'noreply@kaltaniims.com', 'KALTANI',
                    'subject' => "Withdwral",
                    'toreceiver' => $receiveremail,
                );

                Mail::send('withdwral', $data, function ($message) use ($data) {
                    $message->from($data['fromsender']);
                    $message->to($data['toreceiver']);
                    $message->subject($data['subject']);

                });

                $id = $var->data->id;

                $body = json_encode($databody);
                $curl = curl_init();

                $key = env('FLW_SECRET_KEY');
                //"Authorization: $key",
                curl_setopt($curl, CURLOPT_URL, "https://api.flutterwave.com/v3/transfers/$id");
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl, CURLOPT_ENCODING, '');
                curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
                curl_setopt($curl, CURLOPT_TIMEOUT, 0);
                curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
                curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json',
                    'Accept: application/json',
                    "Authorization: $key",
                )
                );

                $var = curl_exec($curl);
                curl_close($curl);

                $var = json_decode($var);

                if ($var->data->status == 'FAILED') {

                    $userwallet = Auth()->user();
                    $useramount = $userwallet->wallet;
                    $refundmoney = (int) $useramount + (int) $var->data->amount;

                    $update = User::where('id', $user_id)
                        ->update(['wallet' => $refundmoney]);
                }

                return response()->json([
                    "status" => $this->SuccessStatus,
                    "message1" => $var,
                    "message2" => "Please try again later",
                ], 200);

            }

            return response()->json(['status' => $this->SuccessStatus, 'message' => $var], 200);

        }
        return response()->json([
            "status" => $this->FailedStatus,
            "message" => "Insufficient Balance",
        ], 401);
    }

    public function transaction_verify(Request $request)
    {

        $user_id = Auth::user()->id;
        $id = $request->id;

        $key = env('FLW_SECRET_KEY');

        $databody = array(

        );

        $body = json_encode($databody);
        $curl = curl_init();

        $key = env('FLW_SECRET_KEY');
        //"Authorization: $key",
        curl_setopt($curl, CURLOPT_URL, "https://api.flutterwave.com/v3/transfers/$id");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_ENCODING, '');
        curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
        curl_setopt($curl, CURLOPT_TIMEOUT, 0);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Accept: application/json',
            "Authorization: $key",
        )
        );

        $var = curl_exec($curl);
        curl_close($curl);

        $var = json_decode($var);

        if ($var->data->status == 'FAILED') {

            $userwallet = Auth()->user();
            $useramount = $userwallet->wallet;
            $refundmoney = (int) $useramount + (int) $var->data->amount;

            $update = User::where('id', $user_id)
                ->update(['wallet' => $refundmoney]);
        }

        return response()->json([
            "status" => $this->SuccessStatus,
            "message1" => $var,
            "message2" => "Please try again later",
        ], 200);

    }

    public function get_fund_transactions(Request $request)
    {

        $transactions = BankTransfer::where('user_id', Auth::id())
            ->get();

        return response()->json([

            'status' => $this->SuccessStatus,
            'data' => $transactions,

        ], 200);

    }

    public function bank_transaction(Request $request)
    {

        $user_id = Auth::id();
        $amount = $request->amount;
        $ref_id = $request->ref_id;

        $transaction = new BankTransfer();
        $transaction->user_id = $user_id;
        $transaction->amount = $amount;
        $transaction->ref_id = $ref_id;
        $transaction->type = 'Bank Transfer';
        $transaction->save();



        $account_number = Bank::where('id', '1')
            ->first()->account_number;

        $account_name = Bank::where('id', '1')
            ->first()->account_name;

        $bank_name = Bank::where('id', '1')
            ->first()->bank_name;

        $transfer = new BankTransfer();
        $transfer->amount = $amount;
        $transfer->user_id = $user_id;
        $transfer->ref_id = $ref_id;
        $transfer->type = "Bank Transfer";
        $transfer->save();

        $api_key = env('ELASTIC_API');
        $from = env('FROM_API');

        $user = User::where('id', Auth::id())
            ->first();

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
                'subject' => 'Fund Wallet With Transfer',
                'to' => "$email",
                'bodyHtml' => view('bank-transfer-notification', compact('f_name', 'amount', 'account_number', 'account_name', 'bank_name', 'ref_id'))->render(),
                'encodingType' => 0,

            ],
        ]);




        $data = array(
            'fromsender' => 'notify@admin.cardy4u.com', 'CARDY',
            'subject' => "Fund Wallet",
            'toreceiver' => 'toluadejimi@gmail.com',
            'amount' => $amount,
            'user' => Auth::user()->f_name.Auth::user()->l_name,
        );

        Mail::send('transfer-admin-email', ["data1" => $data], function ($message) use ($data) {
            $message->from($data['fromsender']);
            $message->to($data['toreceiver']);
            $message->subject($data['subject']);
        });





        $bank = Bank::where('id', 1)
        ->first();


        return response()->json([

            'status' => $this->SuccessStatus,
            'data' => $transaction,
            'bank' => $bank

        ], 200);

    }

    public function instant_funding(Request $request)
    {

        $fpk = env('FLW_SECRET_KEY');
        $tx_ref = $request->trx;
        $transaction_id = $request->transaction_id;

        $check = BankTransfer::where([
            'ref_id' => $transaction_id,
            'status' => 1,
        ])->first()->ref_id ?? null;

        if ($check == $transaction_id) {

            return response()->json([

                'status' => $this->SuccessStatus,
                'message' => 'You are a thief',

            ], 500);
        }

        $user_wallet = EMoney::where('user_id', Auth::id())
            ->first()->current_banance;

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.flutterwave.com/v3/transactions/$transaction_id/verify",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                "Authorization: $fpk",
                'Content-Type: application/json',
            ),
        ));

        $var = curl_exec($curl);
        curl_close($curl);
        $var = json_decode($var);

        $status = $var->status;
        $ref_trans_id = $var->data->id;
        $amount = $var->data->amount;
        $user_id = $var->data->meta->consumer_id;

        if ($status == 'success') {

            $save = new Transaction();
            $save->ref_trans_id = $ref_trans_id;
            $save->transaction_type = 'cash_in';
            $save->debit = $var->data->amount;
            $save->user_id = $user_id;
            $save->note = 'Instant Wallet Funding';
            $save->save();

            $save = new BankTransfer();
            $save->ref_id = $ref_trans_id;
            $save->type = 'Instant Funding';
            $save->amount = $amount;
            $save->status = 1;
            $save->user_id = $user_id;
            $save->save();

            $credit = $user_wallet + $amount;
            $update = EMoney::where('user_id', Auth::id())
                ->update(['current_balance' => $credit]);

            return response()->json([

                'status' => $this->SuccessStatus,
                'message' => 'Transaction Successfull',

            ], 200);

        }return response()->json([

            'status' => $this->SuccessStatus,
            'message' => 'Transaction Failed please try again later',

        ], 500);

    }

    public function bank_details(Request $request)
    {

        $details = Bank::all();


        return response()->json([

            'status' => $this->SuccessStatus,
            'data' => $details,

        ], 200);





    }


    public function transactions(Request $request){

        $user_id = Auth::id();

        $transactions = Transaction::where('user_id', $user_id)
        ->get();

        return response()->json([

            'status' => $this->SuccessStatus,
            'data' => $transactions

        ], 200);

    }

}
