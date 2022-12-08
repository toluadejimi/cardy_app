<?php

namespace App\Http\Controllers;

use App\Models\AccessToken;
use App\Models\AccountRequest;
use App\Models\Charge;
use App\Models\EMoney;
use App\Models\State;
use App\Models\StateLga;
use App\Models\User;
//use Tymon\JwtAuth\Facades\JwtAuth;
use Auth;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Passport\Passport;

class AuthCoontroller extends Controller
{
    public $successStatus = true;
    public $failedStatus = false;

    public function Login(Request $request)
    {
        try {
            //Login to account

            $credentials = request(['phone', 'password']);
            // Passport::tokensExpireIn(Carbon::now()->addDays(3));
            Passport::tokensExpireIn(Carbon::now()->addMinutes(15));
            // Passport::refreshTokensExpireIn(Carbon::now()->addDays(3));
            Passport::refreshTokensExpireIn(Carbon::now()->addMinutes(15));

            if (!auth()->attempt($credentials)) {
                return response()->json([
                    'status' => $this->failedStatus,
                    'message' => 'Invalid Credientials',
                ], 500);
            }

            $token = auth()->user()->createToken('API Token')->accessToken;

            if (Auth::user()->is_email_verified == 0) {

                return response()->json([
                    'status' => $this->failedStatus,
                    'message' => 'Email Not Verified',
                    'token' => $token,

                ], 500);

            }

            if (Auth::user()->is_kyc_verified == 0) {

                return response()->json([
                    'status' => $this->failedStatus,
                    'message' => 'User Not Verified',
                    'token' => $token,
                ], 500);

            }

            return response()->json([
                "status" => $this->successStatus,
                'message' => "login Successfully",
                'token' => $token,
                'expiresIn' => Auth::guard('api')->check(),
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status' => $this->failedStatus,
                'message' => 'Error',
                'errors' => $e->getMessage(),
            ], 401);
        }
    }

    public function verify_email(Request $request)
    {

        $email = $request->email;

        $email_code = $request->email_code;

        $get_email_code = User::where('email', $email)
            ->first()->email_code;

        if ($get_email_code == $email_code) {

            $update = User::where('email', $email)
                ->update(['is_email_verified' => '1']);

            return response()->json([
                'status' => $this->successStatus,
                'message' => 'Email has been successfully Verifed',
            ], 200);

        }

        return response()->json([
            'status' => $this->failedStatus,
            'message' => 'Invalid Code',
        ], 500);

    }

    public function logout()
    {
        auth()->logout();

        return response()->json(['status' => $this->successStatus, 'message' => 'Successfully logged out'], 200);
    }

    public function createNewToken($token)
    {
        return response()->json(
            [
                'status' => $this->successStatus,
                'expiresIn' => auth('api')->factory()->getTTL() * 60 * 60 * 3,
                'user' => auth()->user(),
                'tokenType' => 'Bearer',
                'accessToken' => $token,

            ], 200
        );

    }

    public function register(Request $request)
    {

        $email_code = random_int(100000, 999999);

        $api_key = env('ELASTIC_API');
        $from = env('FROM_API');

        $validator = Validator::make($request->all(), [
            'f_name' => 'required|string|max:255',
            'l_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users|max:255',
            'phone' => 'required|string|max:255',
            'gender' => 'required|string|max:255',
            'pin' => 'required|string|max:255',
            'password' => 'required|string|confirmed|max:255',
        ]);
        if ($validator->fails()) {
            return response()->json([

                'status' => $this->failedStatus,
                'message' => "Email has already been taken",

            ], 400);
        }
        $user = User::create(array_merge(
            $validator->validated(),
            ['password' => Hash::make($request->password)],
            ['pin' => Hash::make($request->pin)],
            ['type' => 2],
            ['email_code' => $email_code],
            ['device_id' => $request->device_id],

        ));

        $token = $user->createToken('API Token')->accessToken;

        $email = $request->email;
        $f_name = $request->f_name;

        //require_once "vendor/autoload.php";
        $client = new Client([
            'base_uri' => 'https://api.elasticemail.com',
        ]);

        // The response to get
        $res = $client->request('GET', '/v2/email/send', [
            'query' => [

                'apikey' => "$api_key",
                'from' => "$from",
                'fromName' => 'Cardy',
                'sender' => "$from",
                'senderName' => 'Cardy',
                'subject' => 'Verification Code',
                'to' => "$email",
                'bodyHtml' => view('verify-notification', compact('email_code', 'f_name'))->render(),
                'encodingType' => 0,

            ],
        ]);

        $body = $res->getBody();
        $array_body = json_decode($body);

        // $deviceId = AccessToken::find(Auth::id());
        // $deviceId->update(['device_id' => $request->device_id]);

        return response()->json([
            'status' => $this->successStatus,
            'message' => 'User successfully registered, Please check your E-mail for verification code',
            'token' => $token,
        ], 200);
    }

    public function refresh()
    {
        return $this->createToken(auth()->refresh());
    }

    public function deviceId(Request $request)
    {
        $deviceId = User::find(Auth::id());
        $deviceId->update(['device_id' => $request->device_id]);
        return response()->json([
            'status' => $this->successStatus,
            'message' => 'DeviceId Updated',
            'user' => auth()->user(),
        ], 200);
    }

    public function updateUser(Request $request)
    {
        $input = $request->all();
        $userid = Auth::guard('api')->user()->id;
        //dd($userid);
        $users = User::find($userid);
        $rules = array(
            'old_password' => 'required',
            'new_password' => 'required|min:6',
            'confirm_password' => 'required|same:new_password',
        );
        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            $arr = array("status" => $this->failedStatus, "message" => $validator->errors()->first());
        } else {
            try {
                if ((Hash::check(request('old_password'), $users->password)) == false) {
                    $arr = array("status" => $this->failedStatus, "message" => "Check your old password.");
                } else if ((Hash::check(request('new_password'), $users->password)) == true) {
                    $arr = array("status" => $this->failedStatus, "message" => "Please enter a password which is not similar then current password.");
                } else {
                    User::where('id', $userid)->update(['password' => Hash::make($input['new_password'])]);
                    $arr = array("status" => $this->successStatus, "message" => "Password updated successfully.");
                }
            } catch (Exception $e) {
                if (isset($e->errorInfo[2])) {
                    $msg = $e->errorInfo[2];
                } else {
                    $msg = $e->getMessage();
                }
                $arr = array("status" => $this->failedStatus, "message" => $msg);
            }
        }
        return \Response::json($arr);
    }

    public function updatePin(Request $request)
    {
        $input = $request->all();
        $userid = Auth::guard('api')->user()->id;
        //dd($userid);
        $users = User::find($userid);
        $rules = array(
            'old_pin' => 'required',
            'new_pin' => 'required|min:4',
            'confirm_pin' => 'required|same:new_pin',
        );
        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            $arr = array("status" => $this->failedStatus, "message" => $validator->errors()->first());
        } else {
            try {
                if ((Hash::check(request('old_pin'), $users->pin)) == false) {
                    $arr = array("status" => $this->failedStatus, "message" => "Check your old pin.");
                } else if ((Hash::check(request('new_pin'), $users->pin)) == true) {
                    $arr = array("status" => $this->failedStatus, "message" => "Please enter a pin which is not similar then current pin.");
                } else {
                    User::where('id', $userid)->update(['pin' => Hash::make($input['new_pin'])]);
                    $arr = array("status" => $this->successStatus, "message" => "Password updated successfully.");
                }
            } catch (Exception $e) {
                if (isset($e->errorInfo[2])) {
                    $msg = $e->errorInfo[2];
                } else {
                    $msg = $e->getMessage();
                }
                $arr = array("status" => $this->failedStatus, "message" => $msg);
            }
        }
        return \Response::json($arr);
    }

    public function updateAccountDetails(Request $request)
    {
        $input = $request->all();

        $account = new AccountRequest();
        $account->account_number = $request->account_number;
        $account->account_name = $request->account_name;
        $account->bank_name = $request->bank_name;
        $account->bank_code = $request->bank_code;
        $account->user_id = Auth::id();
        $account->save();

        return response()->json([
            'status' => $this->successStatus,
            'message' => 'Your request has been sent successfuly',
            'data' => $account,
        ], 200);

    }

    public function kyc_verification(Request $request)
    {

        $user = User::all();

        $first_name = Auth::user()->f_name;
        $last_name = Auth::user()->l_name;
        $phone = Auth::user()->phone;

        $identification_type = $request->identification_type;
        $identification_number = $request->identification_number;
        $identification_url = $request->identification_url;
        $get_dob = $request->dob;
        $dob = date("d-m-Y", strtotime($get_dob));

        if ($request->file('identification_url')) {

            $file = $request->file('identification_url');
            $filename = date('YmdHi') . $file->getClientOriginalName();
            $file->move(public_path('/upload/verify'), $filename);

            $mono_file_url = url('') . "/public/upload/verify/$filename";

        }

        $input = $request->validate([
            'address_line1' => ['required', 'string'],
            'city' => ['required', 'string'],
            'state' => ['required', 'string'],
            'lga' => ['required', 'string'],
            'bvn' => ['required', 'string'],

        ]);

        $address_line1 = $request->input('address_line1');
        $city = $request->input('city');
        $state = $request->input('state');
        $lga = $request->input('lga');
        $bvn = $request->input('bvn');

        $databody = array(

            "address" => array(
                "address_line1" => $address_line1,
                "city" => $city,
                "state" => $state,
                "lga" => $lga,
            ),

            "identity" => array(
                "type" => "$identification_type",
                "number" => "$identification_number",
                "url" => "$mono_file_url",
            ),

            "dob" => array(
                "date" => "$dob",
            ),

            "entity" => "INDIVIDUAL",
            "first_name" => $first_name,
            "last_name" => $last_name,
            "phone" => $phone,
            "bvn" => $bvn,
        );

        $mono_api_key = env('MONO_KEY');

        $body = json_encode($databody);
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, 'https://api.withmono.com/issuing/v1/accountholders');
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

        $message = $var->message;

        // $id = $var[0]->id;
        if ($var->status == "successful") {

            User::where('id', Auth::user()->id)
                ->update([
                    'address_line1' => $request->address_line1,
                    'city' => $request->city,
                    'f_name' => $request->f_name,
                    'l_name' => $request->l_name,
                    'm_name' => $request->m_name,
                    'identification_type' => $identification_type,
                    'identification_number' => $identification_number,
                    'identification_url' => $mono_file_url,
                    'state' => $request->state,
                    'lga' => $request->lga,
                    'bvn' => $request->bvn,
                    'mono_customer_id' => $var->data->id,
                    'is_kyc_verified' => 1,
                    'identity' => 1,
                    'dob' => $dob,

                ]);

            return response()->json([

                'status' => $this->successStatus,
                'message' => 'Your account has been succesffly approved.',

            ], 200);
        }

        return response()->json([

            'status' => $this->failedStatus,
            'message' => "Verification Failed!!.  $message",

        ], 500);
    }

    public function get_user(Request $request)
    {

        $wallet = Emoney::where('user_id', Auth::id())
            ->first()->current_balance;

        $user_id = Auth::id();

        $result = User::where('id', $user_id)
            ->first();

        $token = $request->bearerToken();

        return response()->json([
            'status' => $this->successStatus,
            'user' => $result,
            'wallet' => $wallet,
            'token' => $token,
        ]);

    }

    public function get_states(Request $request)
    {

        $all_states = State::all('name');

        return response()->json([
            'status' => $this->successStatus,
            'data' => $all_states,

        ]);

    }

    public function get_lga(Request $request)
    {

        $state = $request->state;

        $lga = StateLga::where('state', $state)
            ->get();

        return response()->json([
            'status' => $this->successStatus,
            'data' => $lga,

        ]);

    }

    public function get_fees(Request $request)
    {

        $funding_fee = Charge::where('title', 'funding')
            ->first()->amount;

        $get_rate = Charge::where('title', 'rate')->first();
        $rate = $get_rate->amount;

        $get_usd_creation_fee = Charge::where('title', 'usd_card_creation')->first();
        $usd_creation_fee = $get_usd_creation_fee->amount;

        $data = [
            'funding_fee' => $funding_fee,
            'cardy_rate' => $rate,
            'usd_creation_fee' => $usd_creation_fee,
        ];

        return response()->json([

            'status' => $this->successStatus,
            'data' => $data,

        ], 200);

    }

    public function pin_verify(Request $request)
    {

        $pin = $request->pin;

        $user_pin = User::where('id', Auth::id())
            ->first()->pin;

        if ((Hash::check(request('pin'), $user_pin)) == false) {

            return response()->json([

                'status' => $this->failedStatus,
                'message' => 'Invalid Pin',

            ], 500);

        }

        return response()->json([

            'status' => $this->successStatus,
            'message' => 'Pin Valid',

        ], 200);

    }


    public function forgot_pin(Request $request)
    {

        $email = $request->email;

        return view('forgotpin', compact('email'));

    }

    public function forgot_pin_now(Request $request)
    {

        $email = $request->email;

        $input = $request->validate([
            'password' => ['required', 'confirmed', 'string', 'min:1', 'max:4'],
        ]);

        $update = User::where('email', $email)
            ->update([
                'pin' => Hash::make($request->password),
            ]);

        return view('pin-success');

    }

    public function forgot_password(Request $request)
    {

        $email = $request->email;

        return view('forgotemail', compact('email'));

    }

    public function forgot_password_now(Request $request)
    {

        $email = $request->email;

        $input = $request->validate([
            'password' => ['required', 'confirmed', 'string'],
        ]);

        $update = User::where('email', $email)
            ->update([
                'password' => Hash::make($request->password),
            ]);

        return view('success');

    }

}
