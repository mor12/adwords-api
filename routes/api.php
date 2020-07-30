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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/getEnabledCampaigns', 'AdwordController@getEnabledCampaigns');
Route::get('/getAllCampaigns', 'AdwordController@getAllCampaigns');
Route::get('/getAdGroups',          'AdwordController@getAdGroups');
Route::get('/getKeyWords',          'AdwordController@getKeyWords');
Route::get('/getSingleKeyWords',          'AdwordController@getSingleKeyWords');
Route::get('/pauseCampaign',       'AdwordController@pauseCampaign');
Route::get('/playCampaign',        'AdwordController@playCampaign');
Route::get('/getSingleAdGroupReport','AdwordController@getSingleAdGroupReport');
Route::get('/getAllAdGroupReport',  'AdwordController@getAllAdGroupReport');
Route::get('/CallDeatailReport',  'AdwordController@CallDeatailReport');
Route::get('/getAdPerformanceReport',  'AdwordController@getAdPerformanceReport');
Route::get('/setKeywordPlay',  'AdwordController@KeywordPlay');
Route::get('/getKeywordStatus',  'AdwordController@getKeywordStatus');
Route::get('/getKeywordIdeas',  'AdwordController@getKeywordIdeas');
Route::get('/addNegativeKeyword',  'AdwordController@AddNegativeKeyword');
Route::get('/AddNegativeKeywordCustomSharedSet',  'AdwordController@AddNegativeKeywordCustomSharedSet');
Route::get('/AddSharedSet',  'AdwordController@AddSharedSet');
Route::get('/toggleSharedSet',  'AdwordController@toggleSharedSet');
