<?php

use Illuminate\Http\Request;

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


// Landing Page Registration
Route::namespace('API\v1')->prefix('v1')->group(function () {
    Route::get('/log-test', function() {
        Log::channel('dblogging')->debug('/v1/log-test', ['my-string' => 'log me', "run"]);
        return ["result" => true];
    });

    Route::get('/welcome', 'WelcomeController@index');
    Route::post('/login', 'UsersController@authenticate');
    Route::post('/authenticate', 'UsersController@authenticate');

    Route::get('/ping', function() {
        $response = Response::make(gethostname(), 200);
        $response->header('Content-Type', 'text/plain');
        return $response;
    });

    // API Report of Logistics Acceptance
    Route::post('/acceptance-report', 'AcceptanceReportController@store');
    Route::get('/logistic-report/realization-item/{id}', 'AcceptanceReportController@realizationLogisticList');

    // API Logistic Verification
    Route::post('/verification-registration', 'LogisticVerificationController@verificationRegistration');
    Route::post('/verification-resend', 'LogisticVerificationController@verificationRegistration');
    Route::post('/verification-confirmation', 'LogisticVerificationController@verificationConfirmation');

    //Landing Page Registration
    Route::prefix('landing-page-registration')->group(function () {
        Route::post('/agency', 'AgencyController@store');
        Route::post('/applicant', 'ApplicantController@store');
        Route::post('/needs', 'NeedsController@store');
        Route::post('/letter', 'LetterController@store');

        // AREAS, for public
        Route::get('/areas/cities', 'AreasController@getCities');
        Route::get('/areas/subarea', 'AreasController@subArea');

        //Product
        Route::get('/products', 'ProductsController@index');
        Route::get('/product-unit/{id}', 'ProductsController@productUnit');

        //Tracking Application
        Route::get('/tracking', 'TrackController@index');
        Route::get('/tracking/{id}', 'TrackController@show');
    });

    //Insert New Logistic Request Public
    Route::post('/logistic-request', 'LogisticRequestController@store');

    //Master Faskes
    Route::apiResource('/master-faskes', 'MasterFaskesController');
    Route::post('/verify-master-faskes/{id}', 'MasterFaskesController@verify');

    //Master Faskes Type
    Route::apiResource('/master-faskes-type', 'MasterFaskesTypeController');


    //Route for Another App that want integrate data
    Route::middleware('auth-key')->group(function () {
        Route::get('/products-total-request', 'ProductsController@productRequest');
        Route::get('/products-top-request', 'ProductsController@productTopRequest');
        Route::get('/faskes-type-total-request', 'MasterFaskesTypeController@masterFaskesTypeRequest');
        Route::get('/faskes-type-top-request', 'MasterFaskesTypeController@masterFaskesTypeTopRequest');
        Route::get('/logistic-request-summary', 'LogisticRequestController@requestSummary');
        Route::get('/logistic-request/cities/total-request', 'AreasController@getCitiesTotalRequest');
        // Integrate with POSLOG
        Route::get('/logistic-request-list', 'LogisticRequestController@finalList');
        Route::get('/logistic-request/{id}', 'LogisticRequestController@show');
        Route::apiResource('/outbound', 'OutboundController');
        Route::get('/outbound-notification', 'OutboundController@notification');
        Route::get('/sendping', 'OutboundController@sendPing');
        Route::get('/poslog-notify', 'OutboundController@getNotification');
        Route::get('/update-all-lo', 'OutboundController@updateAll');
    });

    Route::middleware('auth:api')->group(function () {
        // USER
        Route::get('/users/me', 'UsersController@me');
        Route::post('/users/register', 'UsersController@register');
        Route::put('/users/change-password', 'UsersController@changePassword');

        // AREAS
        Route::get('/areas/cities', 'AreasController@getCities');
        Route::get('/areas/subdistricts', 'AreasController@getSubDistricts');
        Route::get('/areas/villages', 'AreasController@getVillages');

        // PRODUCTS
        Route::get('/products', 'ProductsController@index');
        Route::get('/products/{id}', 'ProductsController@show');
        Route::get('/products-total-request', 'ProductsController@productRequest');
        Route::get('/products-top-request', 'ProductsController@productTopRequest');

        // TRANSACTIONS
        Route::prefix('/transactions')->group(function() {
            Route::get('/summary', 'TransactionController@summary');
            Route::get('/export', 'TransactionController@export');
            Route::get('/{id}', 'TransactionController@show');
            Route::put('/{id}', 'TransactionController@update');
            Route::delete('/{id}', 'TransactionController@destroy');
            Route::get('/', 'TransactionController@index');
            Route::post('/', 'TransactionController@store');
        });

        Route::prefix('/recipients')->group(function() {
          Route::get('/', 'RecipientController@index');
          Route::get('/rdt-result-summary', 'RecipientController@summary_rdt_result');
          Route::get('/summary', 'RecipientController@summary');
          // need to be last so /summary wont be treated as city_code=summary
          Route::get('/{city_code}', 'RecipientController@show');
        });

        Route::prefix('/recipients-faskes')->group(function() {
          Route::get('/summary', 'RecipientFaskesController@summary');
          Route::get('/export', 'RecipientFaskesController@export');
          Route::get('/', 'RecipientFaskesController@index');
        });

        Route::get('/logistic-request', 'LogisticRequestController@index');
        Route::get('/logistic-request/{id}', 'LogisticRequestController@show');
        Route::post('/logistic-request/verification', 'LogisticRequestController@changeStatus')->name('verification');
        Route::get('/logistic-request/need/list', 'LogisticRequestController@listNeed');
        Route::post('/logistic-request/import', 'LogisticRequestController@import');
        Route::post('/logistic-request/realization', 'LogisticRealizationItemController@store');
        Route::get('/logistic-request/cities/total-request', 'AreasController@getCitiesTotalRequest');
        Route::get('/logistic-request/data/export', 'ExportLogisticRequestController@export');
        Route::post('/logistic-request-non-public', 'LogisticRequestController@store')->name('non-public');
        Route::post('/logistic-request/approval', 'LogisticRequestController@changeStatus')->name('approval');
        Route::post('/logistic-request/final', 'LogisticRequestController@changeStatus')->name('final');
        Route::post('/logistic-request/stock-checking', 'LogisticRequestController@stockCheking');
        Route::post('/logistic-request/letter/{id}', 'LogisticRequestController@uploadLetter');
        Route::post('/logistic-request/identity/{id}', 'LogisticRequestController@uploadApplicantFile');
        Route::post('/logistic-request/urgency', 'LogisticRequestController@urgencyChange');
        Route::post('/logistic-request/return', 'LogisticRequestController@undoStep');
        Route::put('/logistic-request/{id}', 'LogisticRequestController@update');
        Route::post('/logistic-request/applicant-letter/{id}', 'LogisticRequestController@update');
        Route::post('/logistic-request/applicant-identity/{id}', 'LogisticRequestController@update');

        // Logistic Realization Items by Admin
        Route::get('/logistic-admin-realization', 'LogisticRealizationItemController@list');
        Route::post('/logistic-admin-realization', 'LogisticRealizationItemController@add');
        Route::put('/logistic-admin-realization/{id}', 'LogisticRealizationItemController@update');
        Route::delete('/logistic-admin-realization/{id}', 'LogisticRealizationItemController@destroy');

        // STOCK
        Route::get('/stock', 'StockController@index');

        // Outgoing Letter Management
        Route::get('/outgoing-letter', 'OutgoingLetterController@index');
        Route::get('/outgoing-letter-print/{id}', 'OutgoingLetterController@print');
        Route::get('/outgoing-letter/{id}', 'OutgoingLetterController@show');
        Route::post('/outgoing-letter', 'OutgoingLetterController@store');
        Route::post('/outgoing-letter/upload', 'OutgoingLetterController@upload');
        Route::put('/outgoing-letter/{id}', 'OutgoingLetterController@update');

        //Request Letter Management
        Route::get('/application-letter', 'RequestLetterController@index');
        Route::get('/application-letter/search-by-letter-number', 'RequestLetterController@searchByLetterNumber');
        Route::get('/application-letter/{id}', 'RequestLetterController@show');
        Route::post('/application-letter', 'RequestLetterController@store');
        Route::put('/application-letter/{id}', 'RequestLetterController@update');
        Route::delete('/application-letter/{id}', 'RequestLetterController@destroy');

        //Logistic Realization Integrate with PosLog
        Route::get('/logistic-realization/products', 'StockController@index');
        Route::get('/logistic-realization/product-units/{id}', 'StockController@productUnitList');
        Route::get('/logistic-realization/sync', 'LogisticRealizationItemController@integrateMaterial');

        //Incoming Letter Management
        Route::get('/incoming-letter', 'IncomingLetterController@index');
        Route::get('/incoming-letter/{id}', 'IncomingLetterController@show');

        //Dashboard
        Route::get('/faskes-type-total-request', 'MasterFaskesTypeController@masterFaskesTypeRequest');
        Route::get('/faskes-type-top-request', 'MasterFaskesTypeController@masterFaskesTypeTopRequest');
        Route::get('/logistic-request-summary', 'LogisticRequestController@requestSummary');

        //Notification via Whatsapp
        Route::post('/notify', 'ChangeStatusNotifyController@sendNotification');

        // API Acceptance Reports
        Route::apiResource('/acceptance-report', 'AcceptanceReportController')->except('store');
        Route::apiResource('/acceptance-report-detail', 'AcceptanceReportDetailController');
        Route::apiResource('/acceptance-report-evidence', 'AcceptanceReportEvidenceController');
        Route::get('/acceptance-report-statistic', 'AcceptanceReportController@statistic');
    });
});
