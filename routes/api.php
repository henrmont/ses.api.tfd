<?php

use App\Http\Controllers\AccountabilityController;
use App\Http\Controllers\CostAssistanceController;
use App\Http\Controllers\DatasusController;
use App\Http\Controllers\HospitalUnityController;
use App\Http\Controllers\OpinionController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PatientRequestController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\TravelController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\Auth;
use Illuminate\Support\Facades\Route;

Route::middleware(['api', Auth::class])
    ->prefix('users')
    ->name('users.')
    ->controller(UserController::class)
    ->group(function () {
        // Listagem
        Route::get('/', 'getUsers')->name('index');
        Route::get('roles', 'getRoles')->name('roles.index');

        // CRUD principal
        Route::post('/', 'createUser')->name('store');
        Route::put('{user}', 'updateUser')->name('update');
        Route::delete('{user}', 'deleteUser')->name('destroy');

        // Ações de estado e relacionamentos
        Route::patch('{user}/lock', 'lockUser')->name('lock');
        Route::patch('{user}/validate', 'validateUser')->name('validate');
        Route::patch('{user}/roles', 'rolesUser')->name('roles.update');

        // Validações assíncronas (se declaradas no Controller)
        Route::get('exists-email/{email}/{currentEmail?}', 'emailUserExists')->name('exists.email');
        Route::get('exists-cns/{cns}/{currentCns?}', 'cnsUserExists')->name('exists.cns');
    });

Route::middleware(['api', Auth::class])
    ->prefix('roles')
    ->name('roles.')
    ->controller(RoleController::class)
    ->group(function () {
        // Listagens
        Route::get('/', 'getRoles')->name('index');
        Route::get('permissions', 'getPermissions')->name('permissions.index');

        // CRUD principal
        Route::post('/', 'createRole')->name('store');
        Route::put('{role}', 'updateRole')->name('update');
        Route::delete('{role}', 'deleteRole')->name('destroy');
    });

Route::middleware(['api', Auth::class])
    ->prefix('hospital-unity')
    ->controller(HospitalUnityController::class)
    ->group(function () {
        Route::get('get-hospital-unities', 'getHospitalUnities');
        Route::post('create-hospital-unity', 'createHospitalUnity');
        Route::patch('update-hospital-unity/{hospital_unity}', 'updateHospitalUnity');
        Route::delete('delete-hospital-unity/{hospital_unity}', 'deleteHospitalUnity');
    });

Route::middleware(['api', Auth::class])
    ->prefix('datasus')
    ->controller(DatasusController::class)
    ->group(function () {
        Route::get('get-competences', 'getCompetences');
    });

Route::middleware(['api', Auth::class])
    ->prefix('settings')
    ->name('settings.')
    ->controller(SettingController::class)
    ->group(function () {
        // Custos de Diárias
        Route::get('daily-costs', 'getDailyCosts')->name('daily-costs.index');
        Route::patch('daily-costs/{daily_cost}', 'updateDailyCost')->name('daily-costs.update');

        // Alocação Orçamentária
        Route::get('budget-allocation', 'getBudgetAllocation')->name('budget-allocation.index');
        Route::patch('budget-allocation/{budget_allocation}', 'updateBudgetAllocation')->name('budget-allocation.update');
    });

Route::middleware(['api', Auth::class])
    ->prefix('patients')
    ->name('patients.')
    ->controller(PatientController::class)
    ->group(function () {
        // Listagem e CRUD principal
        Route::get('/', 'getPatients')->name('index');
        Route::get('archived', 'getArchivePatients')->name('archived');
        Route::post('/', 'createPatient')->name('store');
        Route::post('{patient_care}', 'updatePatient')->name('update'); // Mantido método POST para suporte a upload de arquivos

        // Ações de estado e movimentações
        Route::patch('{patient_care}/archive', 'archivePatient')->name('archive');
        Route::patch('{patient_care}/move-from-archive', 'movePatientFromArchive')->name('unarchive');
        Route::patch('{patient_care}/move-from-others', 'movePatientFromOthers')->name('transfer-to-me');
        Route::patch('{patient_care}/validate', 'validatePatient')->name('validate');
        Route::patch('{patient_care}/finish-back', 'finishBackPatient')->name('finish-return');

        // Acompanhantes
        Route::get('{patient_care}/escorts', 'getPatientEscorts')->name('escorts.index');
        Route::post('{patient_care}/escorts', 'createPatientEscort')->name('escorts.store');
        Route::post('escorts/{escort}', 'updatePatientEscort')->name('escorts.update'); // Mantido método POST para suporte a upload de arquivos
        Route::delete('escorts/{patient_care_escort}', 'deletePatientEscort')->name('escorts.destroy');

        // Laudos e CIDs
        Route::get('{patient_care}/reports', 'getPatientReports')->name('reports.index');
        Route::get('{patient_care}/cids', 'getCids')->name('cids.index');
        Route::post('{patient_care}/reports', 'createPatientReport')->name('reports.store');
        Route::patch('reports/{report}', 'updatePatientReport')->name('reports.update');
        Route::delete('reports/{report}', 'deletePatientReport')->name('reports.destroy');

        // Anexos do laudo
        Route::get('reports/{report}/attachments', 'getReportAttachments')->name('reports.attachments.index');
        Route::post('reports/{report}/attachments', 'createReportAttachment')->name('reports.attachments.store');
        Route::post('attachments/{report_attachment}', 'updateReportAttachment')->name('reports.attachments.update'); // Mantido método POST para suporte a upload de arquivos
        Route::delete('attachments/{report_attachment}', 'deleteReportAttachment')->name('reports.attachments.destroy');

        // Consultas diretas (Utilizadas para autopreenchimento e validação)
        Route::get('cns/{cns}', 'getPatientCns')->name('search.cns');
        Route::get('document/{document}', 'getPatientDocument')->name('search.document');
        Route::get('escorts/cns/{cns}', 'getEscortCns')->name('escorts.search.cns');
        Route::get('escorts/document/{document}', 'getEscortDocument')->name('escorts.search.document');
    });

Route::middleware(['api', Auth::class])
    ->prefix('patient-requests')
    ->name('patient-requests.')
    ->controller(PatientRequestController::class)
    ->group(function () {
        // Listagem e CRUD principal
        Route::get('/', 'getPatientRequests')->name('index');
        Route::post('/', 'createPatientRequest')->name('store');
        Route::patch('{patient_request}', 'updatePatientRequest')->name('update');
        Route::delete('{patient_request}', 'deletePatientRequest')->name('destroy');

        // Ações de estado e movimentações
        Route::patch('{patient_request}/halted', 'haltedPatientRequest')->name('halt');
        Route::patch('{patient_request}/process-to-medical', 'processPatientRequestToMedical')->name('process-to-medical');
        Route::patch('{patient_request}/move-from-processes', 'movePatientRequestFromProcesses')->name('move-from-processes');
        Route::patch('{patient_request}/move-from-others', 'movePatientRequestFromOthers')->name('move-from-others');
        Route::patch('{patient_request}/move-from-archive', 'movePatientRequestFromArchive')->name('move-from-archive');
        Route::patch('{patient_request}/finish-back', 'finishBackPatientRequest')->name('finish-back');

        // Anexos da solicitação
        Route::get('{patient_request}/attachments', 'getPatientRequestAttachments')->name('attachments.index');
        Route::post('{patient_request}/attachments', 'createPatientRequestAttachment')->name('attachments.store');
        Route::post('attachments/{patient_request_attachment}', 'updatePatientRequestAttachment')->name('attachments.update'); // Mantido método POST para suporte a upload de arquivos
        Route::delete('attachments/{patient_request_attachment}', 'deletePatientRequestAttachment')->name('attachments.destroy');

        // Consultas e dados auxiliares
        Route::get('patients', 'getPatients')->name('patients.index');
        Route::get('patients/{patient_care}/reports', 'getPatientReports')->name('patients.reports.index');
        Route::get('hospital-unities', 'getHospitalUnities')->name('hospital-unities.index');
        Route::get('medical-professionals', 'getMedicalProfessionals')->name('medical-professionals.index');
    });

Route::middleware(['api', Auth::class])
    ->prefix('search')
    ->controller(SearchController::class)
    ->group(function () {
        Route::get('get-patients', 'getPatients');
        Route::get('get-archived-patients', 'getArchivedPatients');
        Route::get('get-archived-patient-requests', 'getArchivedPatientRequests');
    });

Route::middleware(['api', Auth::class])
    ->prefix('opinions')
    ->name('opinions.')
    ->controller(OpinionController::class)
    ->group(function () {
        // Consultas e listagens principais
        Route::get('patient-requests', 'getPatientRequests')->name('patient-requests.index');
        Route::get('patient-requests/archived', 'getArchivePatientRequests')->name('patient-requests.archived');
        Route::get('professional-type', 'getType')->name('professional-type');
        Route::get('reports/{report}/patient-requests/{patient_request}/history', 'getHistoryPatientRequests')->name('patient-requests.history');

        // CRUD do Parecer (Opinions)
        Route::get('patient-requests/{patient_request}', 'getOpinions')->name('index');
        Route::post('patient-requests/{patient_request}', 'createOpinion')->name('store');
        Route::patch('{opinion}', 'updateOpinion')->name('update');
        Route::delete('{opinion}', 'deleteOpinion')->name('destroy');

        // Tramitações e processamentos de solicitações
        Route::patch('patient-requests/{patient_request}/process-to-social', 'processPatientRequestToSocial')->name('patient-requests.process-to-social');
        Route::patch('patient-requests/{patient_request}/process-to-cost-and-travel', 'processPatientRequestToCostAssistanceAndTravel')->name('patient-requests.process-to-cost-and-travel');
        Route::patch('patient-requests/{patient_request}/undo', 'undoPatientRequest')->name('patient-requests.undo');
        Route::patch('patient-requests/{patient_request}/finish-back/{type}', 'finishBackPatientRequest')->name('patient-requests.finish-back');

        // Ações de estado, movimentações e arquivamento
        Route::patch('patient-requests/{patient_request}/archive', 'archivePatientRequest')->name('patient-requests.archive');
        Route::patch('patient-requests/{patient_request}/halted/{type}', 'haltedPatientRequest')->name('patient-requests.halt');
        Route::patch('patient-requests/{patient_request}/move-from-processes/{type}', 'movePatientRequestFromProcesses')->name('patient-requests.move-from-processes');
        Route::patch('patient-requests/{patient_request}/move-from-archive/{type}', 'movePatientRequestFromArchive')->name('patient-requests.move-from-archive');
        Route::patch('patient-requests/{patient_request}/move-from-others/{type}', 'movePatientRequestFromOthers')->name('patient-requests.move-from-others');

        // Consultas de profissionais auxiliares
        Route::get('social-professionals', 'getSocialProfessionals')->name('social-professionals.index');
        Route::get('cost-assistance-professionals', 'getCostAssistanceProfessionals')->name('cost-assistance-professionals.index');
        Route::get('travel-professionals', 'getTravelProfessionals')->name('travel-professionals.index');
    });

Route::middleware(['api', Auth::class])
    ->prefix('travels')
    ->name('travels.')
    ->controller(TravelController::class)
    ->group(function () {
        // Consultas e listagens principais
        Route::get('patient-requests', 'getPatientRequests')->name('patient-requests.index');
        Route::get('patient-requests/archived', 'getArchivePatientRequests')->name('patient-requests.archived');
        Route::get('patient-cares/{patient_care}/escorts', 'getPatientEscorts')->name('patient-cares.escorts');

        // CRUD de Viagens (Travels)
        Route::get('patient-requests/{patient_request}', 'getTravels')->name('index');
        Route::post('patient-requests/{patient_request}', 'createTravel')->name('store');
        Route::patch('{travel}', 'updateTravel')->name('update');
        Route::delete('{travel}', 'deleteTravel')->name('destroy');
        Route::post('import', 'importTravels')->name('import');

        // Passageiros da viagem (Passengers)
        Route::get('{travel}/passengers', 'getPassengers')->name('passengers.index');
        Route::post('{travel}/passengers', 'createPassenger')->name('passengers.store');
        Route::patch('passengers/{passenger}', 'updatePassenger')->name('passengers.update');
        Route::delete('passengers/{passenger}', 'deletePassenger')->name('passengers.destroy');
        Route::get('{travel}/passengers/exists', 'passengerExists')->name('passengers.exists');

        // Rotas da viagem (Routes)
        Route::get('{travel}/routes', 'getTravelRoutes')->name('routes.index');
        Route::post('{travel}/routes', 'createTravelRoute')->name('routes.store');
        Route::patch('routes/{travel_route}', 'updateTravelRoute')->name('routes.update');
        Route::delete('routes/{travel_route}', 'deleteTravelRoute')->name('routes.destroy');

        // Tramitações e processamentos de solicitações
        Route::patch('patient-requests/{patient_request}/escorts', 'patientRequestEscorts')->name('patient-requests.escorts');
        Route::patch('patient-requests/{patient_request}/undo', 'undoPatientRequest')->name('patient-requests.undo');
        Route::patch('patient-requests/{patient_request}/finish-back', 'finishBackPatientRequest')->name('patient-requests.finish-back');

        // Ações de estado, movimentações e arquivamento
        Route::patch('patient-requests/{patient_request}/archive', 'archivePatientRequest')->name('patient-requests.archive');
        Route::patch('patient-requests/{patient_request}/halted', 'haltedPatientRequest')->name('patient-requests.halt');
        Route::patch('patient-requests/{patient_request}/move-from-archive', 'movePatientRequestFromArchive')->name('patient-requests.move-from-archive');
        Route::patch('patient-requests/{patient_request}/move-from-others', 'movePatientRequestFromOthers')->name('patient-requests.move-from-others');
    });

Route::middleware(['api', Auth::class])
    ->prefix('cost-assistances')
    ->name('cost-assistances.')
    ->controller(CostAssistanceController::class)
    ->group(function () {
        // Consultas e listagens de solicitações
        Route::get('patient-requests', 'getPatientRequests')->name('patient-requests.index');
        Route::get('patient-requests/archived', 'getArchivePatientRequests')->name('patient-requests.archived');
        Route::get('patient-requests/{report}/{patient_request}/history', 'getHistoryPatientRequests')->name('patient-requests.history');
        Route::get('patient-cares/{patient_care}/balance', 'getBalance')->name('patient-cares.balance');

        // CRUD de Ajudas de Custo (CostAssistances)
        Route::get('patient-requests/{patient_request}', 'getCostAssistances')->name('index');
        Route::post('patient-requests/{patient_request}', 'createCostAssistance')->name('store');
        Route::patch('{cost_assistance}', 'updateCostAssistance')->name('update');
        Route::delete('{cost_assistance}', 'deleteCostAssistance')->name('destroy');

        // Diárias das Ajudas de Custo (CostAssistanceDailies)
        Route::get('{cost_assistance}/dailies', 'getCostAssistanceDailies')->name('dailies.index');
        Route::post('{cost_assistance}/dailies', 'createCostAssistanceDaily')->name('dailies.store');
        Route::patch('dailies/{cost_assistance_daily}', 'updateCostAssistanceDaily')->name('dailies.update');
        Route::delete('dailies/{cost_assistance_daily}', 'deleteCostAssistanceDaily')->name('dailies.destroy');

        // Tramitações, movimentações e fluxo financeiro
        Route::patch('patient-requests/{patient_request}/undo', 'undoPatientRequest')->name('patient-requests.undo');
        Route::patch('patient-requests/{patient_request}/finish-back', 'finishBackPatientRequest')->name('patient-requests.finish-back');
        Route::patch('patient-requests/{patient_request}/process-to-payment', 'processPatientRequestToPayment')->name('patient-requests.process-to-payment');

        // Ações de estado, movimentações e arquivamento
        Route::patch('patient-requests/{patient_request}/halted', 'haltedPatientRequest')->name('patient-requests.halt');
        Route::patch('patient-requests/{patient_request}/archive', 'archivePatientRequest')->name('patient-requests.archive');
        Route::patch('patient-requests/{patient_request}/move-from-archive', 'movePatientRequestFromArchive')->name('patient-requests.move-from-archive');
        Route::patch('patient-requests/{patient_request}/move-from-history', 'movePatientRequestFromHistory')->name('patient-requests.move-from-history');
        Route::patch('patient-requests/{patient_request}/move-from-others', 'movePatientRequestFromOthers')->name('patient-requests.move-from-others');

        // Consultas auxiliares
        Route::get('daily-costs', 'getDailyCosts')->name('daily-costs.index');
        Route::get('payment-professionals', 'getPaymentProfessionals')->name('payment-professionals.index');
    });

Route::middleware(['api', Auth::class])
    ->prefix('accountabilities')
    ->name('accountabilities.')
    ->controller(AccountabilityController::class)
    ->group(function () {
        // Consultas e listagens de solicitações
        Route::get('patient-requests', 'getPatientRequests')->name('patient-requests.index');
        Route::get('patient-requests/archived', 'getArchivePatientRequests')->name('patient-requests.archived');
        Route::get('patient-cares/{patient_care}/balance', 'getBalance')->name('patient-cares.balance');

        // CRUD de Prestações de Contas (Accountabilities)
        Route::get('patient-requests/{patient_request}', 'getAccountabilities')->name('index');
        Route::post('patient-requests/{patient_request}', 'createAccountability')->name('store');
        Route::patch('{accountability}', 'updateAccountability')->name('update');
        Route::delete('{accountability}', 'deleteAccountability')->name('destroy');

        // Diárias da Prestação de Contas (AccountabilityDailies)
        Route::get('{accountability}/dailies', 'getAccountabilityDailies')->name('dailies.index');
        Route::post('{accountability}/dailies', 'createAccountabilityDaily')->name('dailies.store');
        Route::patch('dailies/{accountability_daily}', 'updateAccountabilityDaily')->name('dailies.update');
        Route::delete('dailies/{accountability_daily}', 'deleteAccountabilityDaily')->name('dailies.destroy');

        // Ações de estado, movimentações e arquivamento
        Route::patch('patient-requests/{patient_request}/halted', 'haltedPatientRequest')->name('patient-requests.halt');
        Route::patch('patient-requests/{patient_request}/archive', 'archivePatientRequest')->name('patient-requests.archive');
        Route::patch('patient-requests/{patient_request}/move-from-others', 'movePatientRequestFromOthers')->name('patient-requests.move');

        // Consultas auxiliares
        Route::get('daily-costs', 'getDailyCosts')->name('daily-costs.index');
    });

Route::middleware(['api', Auth::class])
    ->prefix('payment')
    ->controller(PaymentController::class)
    ->group(function () {
        Route::get('get-payments', 'getPayments');
        Route::get('get-archive-patient-requests', 'getArchivePatientRequests');
        Route::patch('halted-patient-request/{patient_request}', 'haltedPatientRequest');
        Route::patch('update-payment/{payment}', 'updatePayment');
        Route::patch('finish-patient-request-payment/{patient_request}', 'finishPatientRequestPayment');
        Route::patch('undo-patient-request/{patient_request}', 'undoPatientRequest');
        Route::patch('archive-patient-request/{patient_request}', 'archivePatientRequest');
        Route::patch('move-patient-request-from-archive/{patient_request}', 'movePatientRequestFromArchive');
        Route::get('download-merged-pdf/{payment}', 'downloadMergedPdf');
        Route::get('download-memo-pdf/{payment}', 'downloadMemoPdf');
    });

// CHECKS
Route::middleware(['api', Auth::class])
    ->prefix('checks')
    ->group(function () {
        // Patient Checks
        Route::get('get-patient-cns/{cns}', [PatientController::class, 'getPatientCns']);
        Route::get('get-patient-document/{document}', [PatientController::class, 'getPatientDocument']);
        Route::get('get-escort-cns/{cns}', [PatientController::class, 'getEscortCns']);
        Route::get('get-escort-document/{document}', [PatientController::class, 'getEscortDocument']);
    });

// VALIDATORS
Route::middleware(['api', Auth::class])
    ->prefix('validator')
    ->group(function () {
        // User Validators
        Route::get('email-user-exists/{email}/{data?}', [UserController::class, 'emailUserExists']);
        Route::get('cns-user-exists/{cns}/{data?}', [UserController::class, 'cnsUserExists']);
        // Patient Validators
        Route::get('cns-patient-exists/{cns}/{data?}', [PatientController::class, 'cnsPatientExists']);
        Route::get('cns-escort-exists/{patient_care}/{cns}/{data?}', [PatientController::class, 'cnsEscortExists']);
        Route::get('document-patient-exists/{document}/{data?}', [PatientController::class, 'documentPatientExists']);
        Route::get('document-escort-exists/{patient_care}/{document}/{data?}', [PatientController::class, 'documentEscortExists']);
        // Travel Validators
        Route::get('passenger-exists/{travel}', [TravelController::class, 'passengerExists']);
    });



