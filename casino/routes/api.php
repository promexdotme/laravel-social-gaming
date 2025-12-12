<?php

use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['ipcheck']], function () {

    Route::post('/demo', ['uses' => 'BasicController@index']);
    Route::post('/agent/trial', ['uses' => 'BasicController@agent']);

	Route::post('login', 'Auth\AuthController@login');
	Route::post('logout', 'Auth\AuthController@logout');

    if (settings('reg_enabled')) {
        Route::post('register', 'Auth\RegistrationController@index');
        if (settings('use_email')) {
            Route::post('register/verify-email/{token}', 'Auth\RegistrationController@verifyEmail');
        }
    }

    if (settings('forgot_password')) {
        Route::post('password/remind', 'Auth\Password\RemindController@index');
        Route::post('password/reset', 'Auth\Password\ResetController@index');
    }


	Route::get('me', 'Profile\DetailsController@index');
	Route::patch('me/details', 'Profile\DetailsController@update');
	Route::get('me/refund', 'Profile\DetailsController@refunds');
    Route::post('sms', 'Profile\DetailsController@sms');

    Route::post('me/balance', 'Profile\DetailsController@balance');

    Route::resource('users', 'Users\UsersController', [
        'except' => ['create']
    ]);
    Route::post('users/mass', 'Users\UsersController@mass');
    Route::put('users/{user}/balance/{type}', 'Users\BalanceController@balance');


    // shop endpoints removed



	Route::get('games', 'Games\GamesController@index');
    Route::get('category', 'Categories\CategoriesController@index');
	Route::get('jackpots', 'Jackpots\JackpotsController@index');


	Route::get('stats/pay', 'GameStats\GameStatsController@pay');
    Route::get('stats/game', 'GameStats\GameStatsController@game');
    Route::get('stats/shift', 'GameStats\GameStatsController@shift');
    Route::put('shifts/start', 'OpenShiftController@start_shift');
    Route::get('shifts/info', 'OpenShiftController@info');
    Route::get('happyhours', 'HappyHourController@index');
    Route::get('paysystems', 'GeneralController@paysystems');
	
});	
// Custom api's
Route::get('player/getlic', 'Player\LicenseController@AskForLicense');
Route::post('player/licsaved', 'Player\LicenseController@LicSaved');


Route::get('player/isonline', 'Player\StatusController@checkUsecheckUserOnline');
Route::get('player/check-user-login', 'Player\StatusController@checkUserLogin');
Route::get('player/testlogin', 'Player\StatusController@checkUserLoginSyn');
Route::get('player/apilogin/{token}', 'Player\StatusController@apiLogin');
Route::get('player/read', 'Player\StatusController@getUserData');
Route::get('player/score', 'Player\StatusController@checkUserScore');

Route::get('player/withdrawticket', 'Player\TicketController@payoutTicket');


													  
																		  
																				 
Route::get('credits', 'Player\CreditController@index');
Route::get('credits/depositusb', 'Player\CreditController@creditsDeposit');
Route::get('credits/pending-depositusb', 'Player\CreditController@pendingCashIN');

Route::get('cashier/readbalance', 'Player\StatusController@loadShopBalance');
Route::get('cashier/readinamounts', 'Player\StatusController@loadInAmounts');

									   
																		
																		  
																							
																	 
// ==========================================================================================
// V3 APIs Newly developed - 17-07-2021 


/*
Route::prefix('V2')->group(function () {
    // Credit relatedAPIs
    Route::post('/player/depositusb', 'V2\CreditController@creditsDeposit');
    Route::post('/player/withdrawusb', 'V2\CreditController@creditsWithdraw');
    Route::post('/player/withdrawusbcashout', 'V2\CreditController@creditsWithdrawAndCashOut');
    Route::post('/player/withdrawticket', 'V2\CreditController@payoutTicket');

});
*/
