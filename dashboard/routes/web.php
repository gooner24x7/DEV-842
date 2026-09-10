<?php

use Illuminate\Support\Facades\Route;

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

Route::get('/microsoft', 'MicrosoftAuthController@redirectToMicrosoft');
Route::get('/microsoft/callback', 'MicrosoftAuthController@handleMicrosoftCallback');

Route::get('/form/{hash}', 'HomeController@form')->name('form')->where('hash', '(.+)');;

Route::post('/zoho-report', 'ZohoCrmController@index');
Route::any('/zoho/oauth', 'ZohoOAuthController');
Route::post('/stripe/wh', 'WebhookController@handleWebhook')->name('webhook');

Route::get('/{any}', 'HomeController@index')->name('home')->where('any', '^(?!metrics).*$');
