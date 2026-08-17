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
    ->prefix('travel')
    ->controller(TravelController::class)
    ->group(function () {
        Route::get('get-patient-requests', 'getPatientRequests');
        Route::get('get-archive-patient-requests', 'getArchivePatientRequests');
        Route::patch('halted-patient-request/{patient_request}', 'haltedPatientRequest');
        Route::get('get-patient-escorts/{patient_care}', 'getPatientEscorts');
        Route::patch('undo-patient-request/{patient_request}', 'undoPatientRequest');
        Route::patch('archive-patient-request/{patient_request}', 'archivePatientRequest');
        Route::patch('move-patient-request-from-finished/{patient_request}', 'movePatientRequestFromFinished');
        Route::patch('move-patient-request-from-archive/{patient_request}', 'movePatientRequestFromArchive');
        Route::patch('move-patient-request-from-others/{patient_request}', 'movePatientRequestFromOthers');
        Route::post('import-travels', 'importTravels');
        Route::get('get-travels/{patient_request}', 'getTravels');
        Route::post('create-travel/{patient_request}', 'createTravel');
        Route::patch('update-travel/{travel}', 'updateTravel');
        Route::delete('delete-travel/{travel}', 'deleteTravel');
        Route::get('get-passengers/{travel}', 'getPassengers');
        Route::post('create-passenger/{travel}', 'createPassenger');
        Route::patch('update-passenger/{passenger}', 'updatePassenger');
        Route::delete('delete-passenger/{passenger}', 'deletePassenger');
        Route::get('get-travel-routes/{travel}', 'getTravelRoutes');
        Route::post('create-travel-route/{travel}', 'createTravelRoute');
        Route::patch('update-travel-route/{travel_route}', 'updateTravelRoute');
        Route::delete('delete-travel-route/{travel_route}', 'deleteTravelRoute');
        Route::patch('finish-back-patient-request/{patient_request}', 'finishBackPatientRequest');
    });

Route::middleware(['api', Auth::class])
    ->prefix('cost-assistance')
    ->controller(CostAssistanceController::class)
    ->group(function () {
        Route::get('get-patient-requests', 'getPatientRequests');
        Route::patch('halted-patient-request/{patient_request}', 'haltedPatientRequest');
        Route::get('get-cost-assistances/{patient_request}', 'getCostAssistances');
        Route::get('get-balance/{patient_care}', 'getBalance');
        Route::post('create-cost-assistance/{patient_request}', 'createCostAssistance');
        Route::patch('update-cost-assistance/{cost_assistance}', 'updateCostAssistance');
        Route::delete('delete-cost-assistance/{cost_assistance}', 'deleteCostAssistance');
        Route::get('get-cost-assistance-dailies/{cost_assistance}', 'getCostAssistanceDailies');
        Route::get('get-daily-costs', 'getDailyCosts');
        Route::post('create-cost-assistance-daily/{cost_assistance}', 'createCostAssistanceDaily');
        Route::patch('update-cost-assistance-daily/{cost_assistance_daily}', 'updateCostAssistanceDaily');
        Route::delete('delete-cost-assistance-daily/{cost_assistance_daily}', 'deleteCostAssistanceDaily');
        Route::get('get-history-patient-requests/{report}/{patient_request}', 'getHistoryPatientRequests');
        Route::patch('move-patient-request-from-history/{patient_request}', 'movePatientRequestFromHistory');
        Route::patch('move-patient-request-from-processes/{patient_request}', 'movePatientRequestFromProcesses');
        Route::patch('move-patient-request-from-others/{patient_request}', 'movePatientRequestFromOthers');
        Route::patch('undo-patient-request/{patient_request}', 'undoPatientRequest');
        Route::get('get-payment-professionals', 'getPaymentProfessionals');
        Route::patch('process-patient-request-to-payment/{patient_request}', 'processPatientRequestToPayment');
        Route::patch('finish-back-patient-request/{patient_request}', 'finishBackPatientRequest');
    });

Route::middleware(['api', Auth::class])
    ->prefix('accountability')
    ->controller(AccountabilityController::class)
    ->group(function () {
        Route::get('get-patient-requests', 'getPatientRequests');
        Route::get('get-archive-patient-requests', 'getArchivePatientRequests');
        Route::patch('halted-patient-request/{patient_request}', 'haltedPatientRequest');
        Route::get('get-accountabilities/{patient_request}', 'getAccountabilities');
        Route::get('get-balance/{patient_care}', 'getBalance');
        Route::post('create-accountability/{patient_request}', 'createAccountability');
        Route::patch('update-accountability/{accountability}', 'updateAccountability');
        Route::delete('delete-accountability/{accountability}', 'deleteAccountability');
        Route::get('get-accountability-dailies/{accountability}', 'getAccountabilityDailies');
        Route::post('create-accountability-daily/{accountability}', 'createAccountabilityDaily');
        Route::patch('update-accountability-daily/{accountability_daily}', 'updateAccountabilityDaily');
        Route::delete('delete-accountability-daily/{accountability_daily}', 'deleteAccountabilityDaily');
        Route::patch('archive-patient-request/{patient_request}', 'archivePatientRequest');
        Route::patch('move-patient-request-from-archive/{patient_request}', 'movePatientRequestFromArchive');
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



