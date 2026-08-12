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
    ->prefix('user')
    ->controller(UserController::class)
    ->group(function () {
        Route::get('get-users', 'getUsers');
        Route::get('get-roles', 'getRoles');
        Route::post('create-user', 'createUser');
        Route::patch('lock-user/{user}', 'lockUser');
        Route::patch('validate-user/{user}', 'validateUser');
        Route::patch('update-user/{user}', 'updateUser');
        Route::delete('delete-user/{user}', 'deleteUser');
        Route::patch('roles-user/{user}', 'rolesUser');
    });

Route::middleware(['api', Auth::class])
    ->prefix('role')
    ->controller(RoleController::class)
    ->group(function () {
        Route::get('get-roles', 'getRoles');
        Route::get('get-permissions', 'getPermissions');
        Route::post('create-role', 'createRole');
        Route::patch('update-role/{role}', 'updateRole');
        Route::delete('delete-role/{role}', 'deleteRole');
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
    ->prefix('setting')
    ->controller(SettingController::class)
    ->group(function () {
        Route::get('get-daily-costs', 'getDailyCosts');
        Route::patch('update-daily-cost/{daily_cost}', 'updateDailyCost');
        Route::get('get-budget-allocation', 'getBudgetAllocation');
        Route::patch('update-budget-allocation/{budget_allocation}', 'updateBudgetAllocation');
    });

Route::middleware(['api', Auth::class])
    ->prefix('patient')
    ->controller(PatientController::class)
    ->group(function () {
        Route::get('get-patients', 'getPatients');
        Route::get('get-archive-patients', 'getArchivePatients');
        Route::post('create-patient', 'createPatient');
        Route::post('update-patient/{patient_care}', 'updatePatient');
        Route::get('get-patient-escorts/{patient_care}', 'getPatientEscorts');
        Route::post('create-patient-escort/{patient_care}', 'createPatientEscort');
        Route::patch('update-patient-escort/{escort}', 'updatePatientEscort');
        Route::delete('delete-patient-escort/{patient_care_escort}', 'deletePatientEscort');
        Route::get('get-patient-reports/{patient_care}', 'getPatientReports');
        Route::get('get-cids/{patient_care}', 'getCids');
        Route::post('create-patient-report/{patient_care}', 'createPatientReport');
        Route::patch('update-patient-report/{report}', 'updatePatientReport');
        Route::delete('delete-patient-report/{report}', 'deletePatientReport');
        Route::get('get-report-attachments/{report}', 'getReportAttachments');
        Route::post('create-report-attachment/{report}', 'createReportAttachment');
        Route::patch('update-report-attachment/{report_attachment}', 'updateReportAttachment');
        Route::delete('delete-report-attachment/{report_attachment}', 'deleteReportAttachment');
        Route::patch('archive-patient/{patient_care}', 'archivePatient');
        Route::patch('move-patient-from-archive/{patient_care}', 'movePatientFromArchive');
        Route::patch('move-patient-from-others/{patient_care}', 'movePatientFromOthers');
        Route::patch('validate-patient/{patient_care}', 'validatePatient');
        Route::patch('finish-back-patient/{patient_care}', 'finishBackPatient');
    });

Route::middleware(['api', Auth::class])
    ->prefix('patient-request')
    ->controller(PatientRequestController::class)
    ->group(function () {
        Route::get('get-patient-requests', 'getPatientRequests');
        Route::get('get-patients', 'getPatients');
        Route::get('get-patient-reports/{patient_care}', 'getPatientReports');
        Route::get('get-hospital-unities', 'getHospitalUnities');
        Route::get('get-medical-professionals', 'getMedicalProfessionals');
        Route::post('create-patient-request', 'createPatientRequest');
        Route::patch('halted-patient-request/{patient_request}', 'haltedPatientRequest');
        Route::patch('update-patient-request/{patient_request}', 'updatePatientRequest');
        Route::delete('delete-patient-request/{patient_request}', 'deletePatientRequest');
        Route::patch('process-patient-request-to-medical/{patient_request}', 'processPatientRequestToMedical');
        Route::patch('move-patient-request-from-processes/{patient_request}', 'movePatientRequestFromProcesses');
        Route::patch('move-patient-request-from-others/{patient_request}', 'movePatientRequestFromOthers');
        Route::patch('move-patient-request-from-archive/{patient_request}', 'movePatientRequestFromArchive');
        Route::patch('finish-back-patient-request/{patient_request}', 'finishBackPatientRequest');
        Route::get('get-patient-request-attachments/{patient_request}', 'getPatientRequestAttachments');
        Route::post('create-patient-request-attachment/{patient_request}', 'createPatientRequestAttachment');
        Route::patch('update-patient-request-attachment/{patient_request_attachment}', 'updatePatientRequestAttachment');
        Route::delete('delete-patient-request-attachment/{patient_request_attachment}', 'deletePatientRequestAttachment');
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
    ->prefix('opinion')
    ->controller(OpinionController::class)
    ->group(function () {
        Route::get('get-patient-requests', 'getPatientRequests');
        Route::get('get-archive-patient-requests', 'getArchivePatientRequests');
        Route::get('get-type', 'getType');
        Route::get('get-social-professionals', 'getSocialProfessionals');
        Route::get('get-opinions/{patient_request}', 'getOpinions');
        Route::post('create-opinion/{patient_request}', 'createOpinion');
        Route::patch('update-opinion/{opinion}', 'updateOpinion');
        Route::delete('delete-opinion/{opinion}', 'deleteOpinion');
        Route::patch('process-patient-request-to-social/{patient_request}', 'processPatientRequestToSocial');
        Route::patch('undo-patient-request/{patient_request}', 'undoPatientRequest');
        Route::patch('archive-patient-request/{patient_request}', 'archivePatientRequest');
        Route::patch('halted-patient-request/{type}/{patient_request}', 'haltedPatientRequest');
        Route::patch('move-patient-request-from-processes/{type}/{patient_request}', 'movePatientRequestFromProcesses');
        Route::patch('move-patient-request-from-archive/{type}/{patient_request}', 'movePatientRequestFromArchive');
        Route::patch('move-patient-request-from-others/{type}/{patient_request}', 'movePatientRequestFromOthers');
        Route::patch('finish-back-patient-request/{type}/{patient_request}', 'finishBackPatientRequest');
        Route::get('get-history-patient-requests/{report}/{patient_request}', 'getHistoryPatientRequests');
        Route::get('get-cost-assistance-professionals', 'getCostAssistanceProfessionals');
        Route::get('get-travel-professionals', 'getTravelProfessionals');
        Route::patch('process-patient-request-to-cost-assistance-and-travel/{patient_request}', 'processPatientRequestToCostAssistanceAndTravel');
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



