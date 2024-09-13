<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AdminController;
use App\Http\Controllers\Api\V1\AppVersionController;
use App\Http\Controllers\Api\V1\OfflineController;
use App\Http\Controllers\Api\V1\CropController;
use App\Http\Controllers\Api\V1\CultureController;
use App\Http\Controllers\Api\V1\DefensiveController;
use App\Http\Controllers\Api\V1\FertilizerController;
use App\Http\Controllers\Api\V1\HarvestController;
use App\Http\Controllers\Api\V1\InterferenceFactorItemController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\PropertyController;
use App\Http\Controllers\Api\V1\PropertyManagementDataController;
use App\Http\Controllers\Api\V1\PropertyMonitoringController;
use App\Http\Controllers\Api\V1\PropertyRainGaugeController;
use App\Http\Controllers\Api\V1\StockController;
use App\Http\Controllers\Api\V1\AssetController;
use App\Http\Controllers\Api\V1\BankAccountManagementController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ClientManagementController;
use App\Http\Controllers\Api\V1\BankController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\ContentController;
use App\Http\Controllers\Api\V1\CropFileController;
use App\Http\Controllers\Api\V1\ErrorLogController;
use App\Http\Controllers\Api\V1\FinancialMovimentationController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\PeopleManagementController;
use App\Http\Controllers\Api\V1\SupplierManagementController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::namespace('Api')
    ->group(function () {


        Route::middleware(['log_api'])->group(function () {
            Route::prefix('/api')->group(function () {
                Route::post('/login', [AuthController::class, 'login']);


                Route::get('queue-workers', function () {
                    return \Illuminate\Support\Facades\Artisan::call('queue:work');
                });

                // Route::middleware(['auth'])->group(function () {
                //Dashboard
                Route::prefix('/dashboard')->group(function () {
                    Route::get('timeline/{admin_id}', [DashboardController::class, 'getTimeline'])->name('dashboard.getTimeline');
                    Route::get('get-itens/{admin_id}', [DashboardController::class, 'getItens'])->name('dashboard.getItens');
                    Route::get('get-crops/{admin_id}', [DashboardController::class, 'getCrops'])->name('dashboard.getCrops');
                    Route::get('search/{admin_id}', [DashboardController::class, 'search']);
                });

                //Administradores
                Route::prefix('admin')->group(function () {
                    Route::get('logout', [AdminController::class, 'logout']);
                    Route::post('/login', [AdminController::class, 'login']);

                    Route::get('list/{admin_id}', [AdminController::class, 'list'])->name("admin.list");
                    Route::get('read/{id}', [AdminController::class, 'read']);
                    Route::post('store', [AdminController::class, 'store']);
                    Route::put('alter-admin', [AdminController::class, 'alterAdmin']);
                    Route::put('delete', [AdminController::class, 'delete']);
                    Route::post('update', [AdminController::class, 'update']);
                    Route::post('update-attribute', [AdminController::class, 'updateAttribute']);

                    Route::post('/request-new-password', [AdminController::class, 'sendNewPasswordEmail']);

                    Route::post('/request-new-password', [AdminController::class, 'sendNewPasswordEmail']);
                    Route::post('/update-password', [AdminController::class, 'updatePassword']);
                    Route::get('/read-hash/{hash}', [AdminController::class, 'readHash']);
                    Route::post('/update-notification-token', [AdminController::class, 'updateNotificationToken']);
                    Route::post('/remove-notification-token', [AdminController::class, 'removeNotificationToken']);

                    Route::post('/update-actual-harvest', [AdminController::class, 'updateActualHarvest']);

                    Route::middleware(['auth'])->group(function () {
                        Route::get('/verify-token', [AdminController::class, 'verifyToken']);
                    });
                });

                // Insumos
                Route::prefix('inputs')->group(function () {

                    // Culturas
                    Route::prefix('cultures')->group(function () {
                        Route::get('list/{admin_id}', [CultureController::class, 'list'])->name("cultures.list");
                        Route::get('read/{id?}', [CultureController::class, 'read']);
                        Route::post('form', [CultureController::class, 'form']);
                        Route::put('alter-status', [CultureController::class, 'alterStatus']);
                    });

                    // Defensivos
                    Route::prefix('defensives')->group(function () {
                        Route::get('list/{admin_id}', [DefensiveController::class, 'list'])->name("defensives.list");
                        Route::get('read/{id?}', [DefensiveController::class, 'read']);
                        Route::post('form', [DefensiveController::class, 'form']);
                        Route::put('delete', [DefensiveController::class, 'delete']);
                        Route::put('alter-type', [DefensiveController::class, 'alterType']);
                    });

                    // Fertilizantes
                    Route::prefix('fertilizers')->group(function () {
                        Route::get('list/{admin_id}', [FertilizerController::class, 'list'])->name("fertilizers.list");
                        Route::get('read/{id?}', [FertilizerController::class, 'read']);
                        Route::post('form', [FertilizerController::class, 'form']);
                        Route::put('delete', [FertilizerController::class, 'delete']);
                    });
                });

                // Fatores de interferência
                Route::prefix('interference-factors')->group(function () {
                    Route::get('list/{type?}', [InterferenceFactorItemController::class, 'list'])->name('interference_factors.list');
                    Route::get('list-by-join/{crop_join_id?}', [InterferenceFactorItemController::class, 'listByJoin'])->name('interference_factors.listByJoin');
                    Route::get('read/{id?}', [InterferenceFactorItemController::class, 'read']);
                    Route::post('form', [InterferenceFactorItemController::class, 'form']);
                    Route::put('delete', [InterferenceFactorItemController::class, 'delete']);
                });

                // Lavouras
                Route::prefix('crops')->group(function () {
                    Route::get('list/{admin_id}', [CropController::class, 'list'])->name('crops.list');
                    Route::get('read/{id?}', [CropController::class, 'read']);
                    Route::post('form', [CropController::class, 'form']);
                    Route::put('delete', [CropController::class, 'delete']);
                    Route::put('deleteFile', [CropFileController::class, 'delete']);
                    Route::put('alter-property', [CropController::class, 'alterProperty']);
                });

                // Laudos das Lavouras
                Route::prefix('crops-ground')->group(function () {
                    Route::get('list/{admin_id}', [CropFileController::class, 'list']);
                    Route::get('read/{id?}', [CropFileController::class, 'read']);
                    Route::post('form', [CropFileController::class, 'form']);
                    Route::put('delete', [CropFileController::class, 'delete']);
                });

                // Safras
                Route::prefix('harvests')->group(function () {
                    Route::get('list', [HarvestController::class, 'list'])->name("harvests.list");
                    Route::get('read/{id?}', [HarvestController::class, 'read']);
                    Route::get('last', [HarvestController::class, 'last']);
                    Route::post('form', [HarvestController::class, 'form']);
                    Route::put('delete', [HarvestController::class, 'delete']);
                });

                // Produtos
                Route::prefix('products')->group(function () {
                    Route::get('list/{admin_id}', [ProductController::class, 'list']);
                    Route::get('read/{id?}', [ProductController::class, 'read']);
                    Route::post('form', [ProductController::class, 'form']);
                    Route::put('alter-status', [ProductController::class, 'alterStatus']);
                });

                Route::prefix('stocks')->group(function () {
                    Route::get('align-stocks', [StockController::class, 'alignStocks']);

                    // Produtos em estoque
                    Route::prefix('products')->group(function () {
                        Route::get('list/{admin_id}', [StockController::class, 'listProducts']);
                        Route::post('add', [StockController::class, 'addProduct']);
                    });

                    // Entradas
                    Route::prefix('incomings')->group(function () {
                        Route::get('list/{admin_id}/{type?}', [StockController::class, 'listIncomings']);
                        Route::post('add', [StockController::class, 'addIncoming']);
                        Route::post('change', [StockController::class, 'changeIncoming']);
                        Route::put('delete', [StockController::class, 'deleteIncoming']);
                    });

                    // Saídas
                    Route::prefix('exits')->group(function () {
                        Route::get('list/{admin_id}', [StockController::class, 'listExits']);
                    });
                });

                // Propriedades
                Route::prefix('properties')->group(function () {
                    Route::get('list/{admin_id}', [PropertyController::class, 'list'])->name("properties.list");
                    Route::get('read/{id?}', [PropertyController::class, 'read'])->name("properties.read");
                    Route::post('form', [PropertyController::class, 'form']);
                    Route::put('delete', [PropertyController::class, 'delete']);
                    Route::get('read-property-crop-join', [PropertyController::class, 'readPropertyCropJoin'])->name('properties.readPropertyCropJoin');

                    // lavouras (iniciar safra)
                    Route::get('read-linked-crops/{id?}', [PropertyController::class, 'readLinkedCrops'])->name('properties.readLinkedCrops');
                    Route::post('link-crops', [PropertyController::class, 'linkCrops']);
                    Route::put('delete-crop-join', [PropertyController::class, 'deleteCropJoin']);

                    // ler lavouras vinculadas
                    Route::get('read-crops-by-property-and-harvest', [PropertyController::class, 'readCropsByOptions'])->name('properties.readCropsByOptions');

                    // subsafras
                    Route::post('link-subharvest', [PropertyController::class, 'linkSubharvest']);

                    // vincular usuários
                    Route::get('read-linked-admins/{id?}/{property_id?}', [PropertyController::class, 'readLinkedAdmins']);
                    Route::post('link-admins', [PropertyController::class, 'linkAdmins']);

                    // ler histórico de safras
                    Route::get('read-harvest-history/{property_id?}', [PropertyController::class, 'readHarvestHistory']);

                    // detalhes da lavoura
                    // ler detalhes daquela safra (será utilizada no componente geral dos detalhes de lavoura)
                    Route::get('read-property-harvest/{property_crop_join_id?}', [PropertyController::class, 'readPropertyHarvest'])->name('properties.readPropertyHarvest');

                    // ler aba "informações de safra"
                    Route::get('read-crop-havest-details/{property_crop_join_id?}', [PropertyController::class, 'readCropHarvestDetails']);
                    Route::get('read-crop-havest-details-mobile/{property_crop_join_id?}', [PropertyController::class, 'readCropHarvestDetailsMobile'])->name("rain_gauge.listMobile");
                    Route::get('filter-rain-gauge/{property_crop_join_id?}/{type?}/{begin?}/{end?}', [PropertyController::class, 'filterRainGauge'])->name('properties.filterRainGauge');
                    Route::get('filter-disease/{property_crop_join_id?}/{type?}/{begin?}/{end?}', [PropertyController::class, 'filterDisease'])->name('properties.filterDisease');

                    // registros pluviômetros
                    Route::prefix('rain-gauge')->group(function () {
                        Route::post('form', [PropertyRainGaugeController::class, 'form']);
                        Route::put('delete', [PropertyRainGaugeController::class, 'delete']);
                    });

                    // dados de manejo
                    Route::prefix('management-data')->group(function () {
                        Route::post('form', [PropertyManagementDataController::class, 'form']);
                        Route::post('multipleForm', [PropertyManagementDataController::class, 'multipleForm']);
                        Route::put('delete/{type}', [PropertyManagementDataController::class, 'delete']);
                        Route::get('list/{property_crop_join_id}/{admin_id}/{type}', [PropertyManagementDataController::class, 'list'])->name('properties.managementData.list');
                        Route::get('get-area', [PropertyManagementDataController::class, 'getArea']);
                    });

                    // monitoramento
                    Route::prefix('monitoring')->group(function () {
                        Route::post('form', [PropertyMonitoringController::class, 'form']);
                        Route::put('delete', [PropertyMonitoringController::class, 'delete']);
                        Route::put('delete-image', [PropertyMonitoringController::class, 'deleteImage']);
                        Route::put('delete-item', [PropertyMonitoringController::class, 'deleteItem']);
                        Route::get('list/{property_crop_join_id}', [PropertyMonitoringController::class, 'list'])->name('properties.monitoring.list');
                        Route::get('read/{property_crop_join_id}/{date}', [PropertyMonitoringController::class, 'read']);
                        Route::put('change-date', [PropertyMonitoringController::class, 'changeDate']);
                    });

                    // registrar atividades
                    Route::prefix('activity')->group(function () {
                        Route::post('form', [PropertyMonitoringController::class, 'form']);
                    });
                });

                // Bens
                Route::prefix('assets')->group(function () {
                    Route::get('list/{admin_id}', [AssetController::class, 'list']);
                    Route::get('read/{id?}', [AssetController::class, 'read']);
                    Route::post('form', [AssetController::class, 'form']);
                    Route::put('delete', [AssetController::class, 'delete']);
                    Route::put('alter-property', [AssetController::class, 'alterProperty']);
                });

                // Relatórios
                Route::prefix('reports')->group(function () {
                    Route::get('list/{admin_id}/{type}', [ReportController::class, 'list']);
                    Route::get('get-filters-options/{admin_id}', [ReportController::class, 'getOptions']);
                });

                // Conteúdos
                Route::prefix('contents')->group(function () {
                    Route::get('list/{admin_id}/{content_type}', [ContentController::class, 'list']);
                    Route::get('read-items-form/{admin_id}', [ContentController::class, 'readItemsForm']);

                    Route::prefix('categories')->group(function () {
                        Route::get('list', [ContentController::class, 'listCategory']);
                        Route::post('form', [ContentController::class, 'formCategory']);
                        Route::put('delete', [ContentController::class, 'deleteCategory']);
                        Route::post('organize', [ContentController::class, 'organizeCategory']);
                    });

                    Route::get('read/{admin_id}/{id?}', [ContentController::class, 'read']);
                    Route::post('form', [ContentController::class, 'form']);
                    Route::put('delete', [ContentController::class, 'delete']);
                    Route::put('delete-image', [ContentController::class, 'deleteImage']);

                    Route::post('save-interaction', [ContentController::class, 'saveInteraction']);
                    Route::post('update-watched', [ContentController::class, 'updateWatched']);

                    Route::post('form-comment', [ContentController::class, 'formComment']);
                    Route::post('like-comment', [ContentController::class, 'likeComment']);
                    Route::post('remove-comment', [ContentController::class, 'removeComment']);

                    Route::get('list-access-type/{type}', [ContentController::class, 'listAccessType']);
                    Route::post('form-access-type', [ContentController::class, 'formAccessType']);
                });

                //Financeiro
                Route::prefix('financial')->group(function () {
                    Route::prefix('management')->group(function () {

                        // Pessoas
                        Route::prefix('people')->group(function () {
                            Route::get('list/{admin_id}', [PeopleManagementController::class, 'list']);
                            Route::get('read/{id?}', [PeopleManagementController::class, 'read']);
                            Route::post('form', [PeopleManagementController::class, 'form']);
                            Route::put('delete', [PeopleManagementController::class, 'delete']);
                            Route::put('alter-status', [PeopleManagementController::class, 'alterStatus']);
                        });

                        // Fornecedores
                        Route::prefix('suppliers')->group(function () {
                            Route::get('list/{admin_id}', [SupplierManagementController::class, 'list']);
                            Route::get('read/{id?}', [SupplierManagementController::class, 'read']);
                            Route::post('form', [SupplierManagementController::class, 'form']);
                            Route::put('delete', [SupplierManagementController::class, 'delete']);
                            Route::put('alter-type', [SupplierManagementController::class, 'alterType']);
                        });

                        // Conta bancária
                        Route::prefix('accounts')->group(function () {
                            Route::get('list/{admin_id}', [BankAccountManagementController::class, 'list']);
                            Route::get('read/{id?}', [BankAccountManagementController::class, 'read']);
                            Route::post('form', [BankAccountManagementController::class, 'form']);
                            Route::put('delete', [BankAccountManagementController::class, 'delete']);
                            Route::put('alter-status', [BankAccountManagementController::class, 'alterStatus']);
                        });

                        // Clientes
                        Route::prefix('clients')->group(function () {
                            Route::get('list/{admin_id}', [ClientManagementController::class, 'list']);
                            Route::get('read/{id?}', [ClientManagementController::class, 'read']);
                            Route::post('form', [ClientManagementController::class, 'form']);
                            Route::put('delete', [ClientManagementController::class, 'delete']);
                        });
                        // Bancos disponíveis no sistema
                        Route::prefix('banks')->group(function () {
                            Route::get('list/{admin_id}', [BankController::class, 'list']);
                            Route::get('read/{id?}', [BankController::class, 'read']);
                            Route::post('form', [BankController::class, 'form']);
                            Route::put('delete', [BankController::class, 'delete']);
                        });
                    });

                    Route::prefix('movimentations')->group(function () {
                        Route::get('list/{admin_id}', [FinancialMovimentationController::class, 'list']);
                        Route::get('list-items-form/{admin_id}', [FinancialMovimentationController::class, 'listItemsForm']);

                        Route::get('read/{id}', [FinancialMovimentationController::class, 'read']);

                        Route::post('conciliate', [FinancialMovimentationController::class, 'conciliate']);
                        Route::post('form-movimentation', [FinancialMovimentationController::class, 'formMovimentation']);
                        Route::post('form-transfer', [FinancialMovimentationController::class, 'formTransfer']);
                        Route::post('form-category', [FinancialMovimentationController::class, 'formCategory']);
                        Route::post('form-injection', [FinancialMovimentationController::class, 'formInjection']);
                        Route::put('delete-movimentation', [FinancialMovimentationController::class, 'deleteMovimentation']);
                        Route::put('delete-transfer-file', [FinancialMovimentationController::class, 'deleteTransferFile']);
                    });
                });

                Route::prefix('offline')->group(function () {
                    Route::get('get-first-part/{admin_id}', [OfflineController::class, 'getFirstPart']);
                    Route::get('get-second-part/{admin_id}', [OfflineController::class, 'getSecondPart']);
                    Route::get('get-partial-sync/{admin_id}', [OfflineController::class, 'getPartialSync']);
                });

                // Notificações
                // Fatores de interferência
                Route::prefix('notifications')->group(function () {
                    Route::get('list/{admin_id}', [NotificationController::class, 'list']);
                });

                Route::prefix('settings')->group(function () {
                    Route::get('app-version', [AppVersionController::class, 'getVersion']);
                });

                Route::prefix('error-log')->group(function () {
                    Route::post('form', [ErrorLogController::class, 'form']);
                });
            });
            // });
        });
    });
