<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

//added by coa
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
//use App\Http\Controllers\AssetController;


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

//Auth::routes(['verify' => true]); //Enables email verification for the authentication routes

// public routes ... available to guests / unauthenticated users
Route::group(['middleware' => ['cors', 'json.response']], function () {

    //MUST DELETE 
    Route::get('/testing/delete/user/{email}','UserController@danger')->name('danger.api');
    Route::get('/status', 'Auth\ApiAuthController@who_is_in');

    // Standard Authentication
    Route::post('/login', 'Auth\ApiAuthController@login')->name('login.api'); //APiAuthController's login fcn has been Modified to allow this work with email verification as well
    Route::post('/register','Auth\ApiAuthController@register')->name('register.api');
    
    Route::get('/email/verify/{id}/{hash}', 'Auth\VerificationController@verify')->middleware(['signed'])->name('verification.verify');
    Route::post('/resend/email/verification', 'Auth\VerificationController@resend')->middleware(['throttle:6,1'])->name('verification.send');

    //CUSTOM Email verifiation 
    //Route::get('/verify_email/{token}', 'Auth\VerificationController@verify')->name('verification.token');  //This route resides in the frontend. Not in use here
    Route::put('/verify/user/email', 'CustomVerificationController@customVerify')->name('verification.custom_verify');
    
    //FORGOT PASSWORD 
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkResponse'])->name('passwords.sent');
    Route::post('/reset-password', [ResetPasswordController::class, 'sendResetResponse'])->name('passwords.reset');
   
    //SOCIAL LOGIN  //These urls are referenced in config/services.php where the providers are registered. If you change them here, change them there as well
    Route::post('/gosocial/callback', 'Auth\LoginController@handleProviderCallback')->name('social.callback');
 
    //PAYMENT ROUTES - 2
    Route::get('payment/get_plans', 'PaymentController@get_plans');

    //RECOVERED -- LOST BUT FOUND ITEMS  
    Route::get('/lost-but-found/assets', 'RecoveryController@listFound');
    Route::post('/report/found/asset', 'RecoveryController@reportFound');

    //TESTING SEND EMAIL STANDALONE -- to be used for sending out emails if necessary
    //These sendmail routes are currently not in use, but may be needed later... The functions are not in the specified controller, except in the backed up version
    //Route::get('sendmail', [ForgotPasswordController::class, 'sendEmail'])->name('send.mail');  //Works great
    //Route::post('sendmail', [ForgotPasswordController::class, 'sendEmail'])->name('send.mail');  

    //NOT required? added to provide the frontend dev the reset password token IF REQUIRED
    Route::get('/password-reset-token', 'Auth\ResetPasswordController@ShowPasswordForm')->name('newpasswordform.api');

    //ASSETS
    //Query for a specific asset ... available to the pulic
    //Route::post('/find/asset/{ref}', 'AssetController@show')->middleware(['sanitize', 'log.route']);  //ref is either skydahid or assetid
    Route::post('/find/asset', 'AssetController@show')->middleware(['sanitize', 'log.route']);  //ref is either skydahid or assetid
    //Route::get('/assets', 'AssetController@index')->name('assets.api')->middleware('verified'); //Guests should be able to verify assets, that's why this isn't in the protected route below

    //Moved out of PROTECTED ROUTES for frontdevs
    Route::get('/user/roles', 'RoleController@index');

    Route::post('/asset/history', 'TransferController@get_ownership_history');
    Route::get('/asset/types', 'TypeController@index');
    Route::get('/asset/type/dropdown', 'TypeController@dropdown');

    //OTP Email Verification ... there's another otp routes in protected section
    Route::post('/send/otp/email', 'OtpController@sendOTP');
    Route::post('/validate/otp/email', 'OtpController@verifyOTP');

    Route::get('/trial', function () {
    
        return view('verified',[
            //'users' => App\Models\User::all()
        ]);
    });

});
Route::group(['middleware' => ['cors', 'json.response', 'auth:api']], function () {
// protected routes will be placed here
//Route::middleware(['cors', 'json.response'])->group(function () {  
////Route::middleware(['cors', 'json.response', 'auth:api'])->group(function () {    
    Route::post('/logout', 'Auth\ApiAuthController@logout')->name('logout.api');
    Route::post('/change-password', 'Auth\ApiAuthController@changePassword')->name('password.change');

    //USER MANAGEMENT ROUTES        //INCLUDE ROUTES FOR STATS
    //MAKE DOUBLE SURE BEFORE ALLOWING A USER TO DO THIS
    Route::post('/delete/account', 'UserController@destroySelf');   //deletes own account
    Route::post('/delete/user', 'UserController@destroy')->middleware(['api.superAdmin']);          //deletes another user (superAdmin)
    Route::post('/find/user', 'UserController@show')->middleware(['api.admin']);          
    Route::post('/modify/user', 'UserController@update');          
    Route::get('/list/users', 'UserController@index')->name('list.users')->middleware(['api.superAdmin']);
    Route::get('/user/profile', 'UserController@getUserOwnData');          

        //Get the password reset form after the reset link is clicked
    Route::get('/reset-password/{token}', 'Auth\ResetPasswordController@getNewPassword')->name('newpassword.api');
    
    //Used for Access Control --- see kernel.php for where I defined them
    //    Route::get('/assets', 'AssetController@index')->middleware('api.admin')->name('assets.api');
    //    Route::get('/assets', 'AssetController@index')->middleware('api.superAdmin')->name('assets.api');
    //

    Route::prefix('asset')->middleware('log.route')->group(function () {
    //Route::prefix('asset')->group(function () {
        Route::post('/add', 'AssetController@add_asset')->middleware(['pinop', 'notFree']);
        Route::post('/generate_company_codes', 'AssetController@generate_company_codes')->middleware(['enterprise', 'api.admin', 'api.superAdmin']);
        Route::get('/get_company_codes/{id}', 'AssetController@get_company_codes')->middleware(['enterprise', 'api.admin', 'api.superAdmin']);
        Route::post('/upload_bulk_assets', 'AssetController@upload_bulk_assets');

        //COA routes:a
        Route::get('/list', 'AssetController@index');
        Route::post('/modify', 'AssetController@update')->middleware('pinop');
        Route::post('/delete', 'AssetController@destroy');
        Route::post('/transfer', 'AssetController@transfer')->middleware('pinop');
        Route::post('/confirm/transfer', 'AssetController@transfer')->middleware('pinop');   //Owner responds YES when alerted of attempt to register asset
        Route::post('/decline/transfer', 'AssetController@transfer');   //Owner responds LOST when alerted of attempt to register asset
     //    Route::post('/add/document', 'AssetController@uploadFile');
        Route::post('/flag/lost', 'AssetController@flagAssetAsMissing');
        Route::post('/flag/found', 'AssetController@flagAssetAsFound');
        Route::get('/list/missing', 'AssetController@listMissingAssets');
        Route::post('/add/type', 'TypeController@store');
        Route::post('/edit/type', 'TypeController@update');
        Route::post('/delete/type', 'TypeController@destroy');
        Route::post('/recoveries', 'RecoveryController@show');
        Route::post('/transfers', 'TransferController@show');
        Route::post('/graph/data', 'AssetController@getGraphData');

        Route::post('/import', 'AssetController@bulkTransfer')->middleware('pinop');

       //COA routes:z
    });

    //Company routes ... user must be logged in to be able to add company
    Route::post('/add/company', 'CompanyController@add_company');
    Route::post('/add/company/user', 'CompanyController@addCompanyUser')->middleware('company.admin');

    Route::middleware('log.route')->group(function () {

        Route::prefix('email')->group(function () {
            Route::post('/send_email', 'EmailServiceController@send_email');
        });

        Route::prefix('sms')->group(function () {
            Route::post('/send_sms', 'SmsServiceController@send_user_sms');
        });

        Route::prefix('payment')->group(function () {
            Route::post('/save_payment', 'PaymentController@save_payment');
            Route::post('/get_user_payments', 'PaymentController@get_user_payments');
        });


        Route::middleware(['api.superAdmin'])->group(function () {    
            Route::get('/user/groups', 'GroupController@index');
            Route::post('/add/user/group', 'GroupController@store');
            Route::post('/edit/user/group', 'GroupController@update');
            Route::post('/delete/user/group', 'GroupController@destroy');
            Route::Get('/asset/recovery/list', 'RecoveryController@index')->middleware('agency');
            Route::Get('/asset/transfer/list', 'TransferController@index');
            Route::get('/list/companies', 'CompanyController@index');
                
            //Route::get('/user/roles', 'RoleController@index');
            Route::post('/add/user/role', 'RoleController@store');
            Route::post('/edit/user/role', 'RoleController@update');
            Route::post('/delete/user/role', 'RoleController@destroy');

            //Payment Plans
            Route::post('/create_plan', 'PaymentController@create_plan');
            Route::post('/edit_plan', 'PaymentController@edit_plan');
            Route::get('/delete_plan/{id}', 'PaymentController@delete_plan');
        });
         //OTP  //there's another OTP routes in public section
        Route::post('/send/otp', 'AssetController@sendOTP');
        Route::post('/validate/otp', 'AssetController@verifyOTP');

        Route::middleware(['api.superAdmin'])->group(function () {
            Route::post('/add/setting', 'SettingController@store');
            Route::post('/edit/setting', 'SettingController@update');
            Route::get('/view/setting', 'SettingController@index');
            Route::post('/delete/setting', 'SettingController@destroy');
        });

    });

    Route::prefix('corp')->middleware(['log.route', 'company.admin'])    //'delegated', 'api.superAdmin'])
            ->group(function () {
        
        //All company/enterprise routes will be added here
        Route::get('/list/company/assets', 'AssetController@company_assets');

        Route::put('/edit/company', 'CompanyController@edit_company');
        Route::get('/view/company', 'CompanyController@index');
        Route::delete('/delete/company', 'CompanyController@destroy');
        
        Route::post('/assign/role', 'RoleController@assign');
        Route::post('/revoke/role', 'RoleController@revoke');

        //Company Users/Staff
        Route::get('/list/users', 'CompanyController@listCompanyUsers');
        Route::get('/user/detail', 'CompanyController@viewCompanyUser');
        Route::delete('/delete/user', 'CompanyController@deleteCompanyUser');
        Route::put('/edit/user', 'CompanyController@editCompanyUser');
    });

    Route::get('/notification/get/detail', 'NotificationController@getNotificationDetail');
    Route::get('/notification/get/summary', 'NotificationController@getNotificationSummary');
    Route::get('/notification/get/unread', 'NotificationController@getUnreadNotification');
    Route::get('/notification/get/read', 'NotificationController@getReadNotification');
    Route::put('/notification/mark/unread/all', 'NotificationController@markAllAsUnread');
    Route::put('/notification/mark/read/all', 'NotificationController@markAllAsRead');
    Route::put('/notification/mark/read', 'NotificationController@markRead');
    Route::put('/notification/mark/unread', 'NotificationController@markUnread');
    
    Route::put('/notification/enable', 'NotificationController@enable');
    Route::put('/notification/disable', 'NotificationController@disable');

    Route::prefix('settings')->group(function () {
        Route::post('/set2fa', 'SettingController@set2fa');
        Route::post('/set/boolean', 'SettingController@addBooleanSetting');

        //Setting options
        Route::get('/list/options', 'OptionController@index');
        Route::middleware(['api.admin'])->group(function () {
            Route::post('/add/options', 'OptionController@store');
            Route::put('/modify/options', 'OptionController@update');
            Route::delete('/delete/options', 'OptionController@destroy');
        });
    });

    //Socials
    Route::get('/list/socials', 'SocialController@index');
    Route::middleware(['api.admin'])->group(function () {
        Route::post('/add/socials', 'SocialController@store');
        Route::put('/modify/socials', 'SocialController@update');
        Route::delete('/delete/socials', 'SocialController@destroy');
        });
    Route::post('/link/socials', 'SocialController@linkSocials');
    Route::post('/unlink/socials', 'SocialController@unlinkSocials');
});



