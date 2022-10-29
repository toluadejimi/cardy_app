<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthCoontroller;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\SortingController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\FactoryController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\TransferController;
use App\Http\Controllers\BailingController;
use App\Http\Controllers\RecycleController;
use App\Http\Controllers\ItemsController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SortedTransferController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\CardController;



/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum', 'access')->get('/user', function (Request $request) {
    return $request->user();
});


//users Auth
Route::post('create-user', [AuthCoontroller::class, 'register']);
Route::post('updatepassword', [AuthCoontroller::class,'updateUser']);
Route::post('login', [AuthCoontroller::class, 'login']);

Route::get('all-state', [AuthCoontroller::class, 'get_states']);

Route::get('get-lga', [AuthCoontroller::class, 'get_lga']);

Route::get('get-fees', [AuthCoontroller::class, 'get_fees']);

















Route::group(['middleware' => ['auth:api','access']], function(){


Route::post('deviceId', [AuthCoontroller::class, 'deviceId']);

Route::post('get-user', [AuthCoontroller::class, 'get_user']);
Route::post('kyc-verification', [AuthCoontroller::class, 'kyc_verification']);



Route::post('verify-email', [AuthCoontroller::class, 'verify_email']);




//cards

Route::get('get-card-details', [CardController::class, 'get_card_details']);
Route::post('create-usd-card', [CardController::class, 'create_usd_card']);




});









