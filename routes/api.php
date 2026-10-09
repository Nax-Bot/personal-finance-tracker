<?php

namespace routes;

use App\Controllers\AccountController;
use App\Controllers\CategoryController;
use App\Controllers\TransactionController;
use App\Controllers\SummaryController;

use App\Database\Database;

use App\Core\Route;

use App\Controllers\JsonResponse;

//initialiser la connexion à la base de données
$database = new Database();
// récupérer la connexion PDO
$pdo = $database->getConnection();


$accountController = new AccountController($pdo);

/************************* ACCOUNTS ************************/

Route::get('GET', '/accounts', [$accountController, 'index']);
Route::get('POST', '/accounts', [$accountController, 'create']);
Route::get('GET', '/accounts/(\d+)', [$accountController, 'show']);
Route::get('PATCH', '/accounts/(\d+)', [$accountController, 'update']);
Route::get('DELETE', '/accounts/(\d+)', [$accountController, 'delete']);

/************************* CATEGORIES ************************/
$categoryController = new CategoryController($pdo);

Route::get('GET', '/categories', [$categoryController, 'index']);
Route::get('POST', '/categories', [$categoryController, 'create']);
Route::get('GET', '/categories/(\d+)', [$categoryController, 'show']);
Route::get('PATCH', '/categories/(\d+)', [$categoryController, 'update']);
Route::get('DELETE', '/categories/(\d+)', [$categoryController, 'delete']);

/************************* TRANSACTIONS ************************/
$transactionController = new TransactionController($pdo);

Route::get('GET', '/transaction', [$transactionController, 'index']);
Route::get('POST', '/transaction', [$transactionController, 'store']);
Route::get('GET', '/transaction/(\d+)', [$transactionController, 'show']);
Route::get('PATCH', '/transaction/(\d+)', [$transactionController, 'update']);
Route::get('DELETE', '/transaction/(\d+)', [$transactionController, 'delete']);
Route::get('GET', '/transaction/month/(\d{4}-\d{2})',[$transactionController, 'monthly']);


/************************* TRANSACTIONS / ACCOUNTS *********************/
Route::get('GET', '/account/(\d+)/transactions', [$transactionController, 'accountTransaction']);

/************************* MONTHLY SUMMARY ****************************/
$summaryController = new SummaryController($pdo);

Route::get('GET', '/summary', [$summaryController, 'index']);

JsonResponse::send([
    'success' => false,
    'error' => 'Route not found'
], 404);
