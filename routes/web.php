<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
 */

Route::get('/', function () { 
    return view('welcome');
});

Route::get('/test', function () {
    return view('test');
});

Route::post('/test', 'VowController@test');
Route::get('/update-photo/{ml_num}', 'VowController@updatePhoto');
Route::get('/update-sold-photo/{table}/{ml_num}', 'VowController@updateSoldPhoto');
Route::get('/remove-condo-deleted-rows', 'VowController@removeCondoDeletedRows');
Route::get('/remove-residential-deleted-rows', 'VowController@removeResidentialDeletedRows');



// Debugging
Route::get('searchmls_treb/{mlsid}', 'TrebController@searchMlsResi');
Route::get('searchmls/{mlsid}/{type}', 'VowController@searchMls');
Route::get('count/{type}', 'TrebController@countResultByType');
Route::get('testvow/{username}/{password}', 'VowController@testConnection');

Route::get('createtable', 'CsvController@createTable');

Route::get('import_sold_data', 'CsvController@import_sold_data');

Route::get('download_csv_files', 'CsvController@download_csv_files');
Route::get('import_sold_residential', 'ImportSoldResidentialController@importSoldResidential');



// Logs
Route::get('cronlog', 'TrebController@cronlog');
Route::get('log', 'TrebController@log');
Route::get('clearlog', 'TrebController@clearlog');


// IDX
Route::get('scripts/residential', 'Scripts\ImportResidentialProperties@index')->name('importresidential');
Route::get('scripts/commercial', 'Scripts\ImportCommercialProperties@index')->name('importcommercial');
Route::get('scripts/condo', 'Scripts\ImportCondoProperties@index')->name('importcondo');

Route::prefix('schema')->group(function () {
    Route::get('residential/create', 'TrebController@resi');
    Route::get('residential/update', 'TrebController@resiUpdate');
    Route::get('commercial/create', 'TrebController@com');
    Route::get('commercial/update', 'TrebController@comUpdate');
    Route::get('condo/create', 'TrebController@condo');
    Route::get('condo/update', 'TrebController@condoUpdate');
});

Route::prefix('available')->group(function () {
    Route::get('residential/download', 'TrebController@resiAvaliableDownload');
    Route::get('commercial/download', 'TrebController@comAvaliableDownload');
    Route::get('condo/download', 'TrebController@condoAvaliableDownload');
    Route::get('residential/remove', 'TrebController@resiRemoveExtraRows');
    Route::get('commercial/remove', 'TrebController@comRemoveExtraRows');
    Route::get('condo/remove', 'TrebController@condoRemoveExtraRows');
});

Route::prefix('object')->group(function () {
    Route::get('residential', 'TrebController@resiObject');
    Route::get('commercial', 'TrebController@comObject');
    Route::get('condo', 'TrebController@condoObject');
    Route::get('sold_condo', 'TrebController@soldCondoObject');
    Route::get('sold_residential', 'TrebController@soldResidentialObject');
});

// VOW
Route::prefix('vow')->group(function () {
    // Create Table
	Route::get('residential/create', 'VowController@createResidentialTable');
	// Route::get('commercial/create', 'VowController@createCommercialTable');
	Route::get('condo/create', 'VowController@createCondoTable');
    
    // Download Data
	Route::get('residential/download', 'VowController@downloadResidential');
	// Route::get('commercial/download', 'VowController@downloadCommerial');
    Route::get('condo/download', 'VowController@downloadCondo');
    
    // Object
    Route::get('residential/object', 'VowController@resiObject');
	// Route::get('commercial/object', 'VowController@comObject');
    Route::get('condo/object', 'VowController@condoObject');

    // Update
    Route::get('residential/update', 'VowController@resiUpdate');
    // Route::get('commercial/update', 'VowController@comUpdate');
    Route::get('condo/update', 'VowController@condoUpdate');
});



