<?php

use App\Http\Controllers\ApiVendasController;
use App\Http\Controllers\ApiTurmasController;
use App\Http\Controllers\ApiVideosController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::apiResource('/sales',ApiVendasController::class);

Route::get('/salesyear',[ApiVendasController::class,'sales_year']);

Route::get('/salesyear/simulations',[ApiVendasController::class,'sales_simulations_year']);

Route::get('/salesyear/classes',[ApiVendasController::class,'sales_product_year']);

Route::apiResource('/turmas',ApiTurmasController::class);

Route::get('/teste',[ApiTurmasController::class,'teste']);

Route::get('/aprovacoes',[ApiTurmasController::class,'aprovacoes']);

Route::get('/alunos',[ApiTurmasController::class, 'alunos_turmas']);

Route::get('/videos',[ApiVideosController::class,'videos']);
