<?php

use App\Http\Controllers\Api\AnnouncementController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\CurrentUserController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\DesignationController;
use App\Http\Controllers\Api\EmployeeBirthdayTimelineController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\EmployeeDocumentController;
use App\Http\Controllers\Api\LeaveRequestController;
use App\Http\Controllers\Api\LeaveTypeController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\Payroll\EssPayslipController;
use App\Http\Controllers\Api\Payroll\PayrollRunController;
use App\Http\Controllers\Api\Recruitment\ApplicationInterviewController;
use App\Http\Controllers\Api\Recruitment\JobApplicationController;
use App\Http\Controllers\Api\Recruitment\JobPositionController;
use App\Http\Controllers\Api\ZktecoController;
use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

    Route::prefix('api')->group(function () {
        Route::get('/me', [CurrentUserController::class, 'show']);
        Route::get('/company/birthdays-timeline', [EmployeeBirthdayTimelineController::class, 'index']);
        Route::get('/announcements/feed', [AnnouncementController::class, 'feed']);
        Route::get('/announcements', [AnnouncementController::class, 'index']);
        Route::post('/announcements', [AnnouncementController::class, 'store']);
        Route::put('/announcements/{announcement}', [AnnouncementController::class, 'update']);
        Route::delete('/announcements/{announcement}', [AnnouncementController::class, 'destroy']);
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead']);

        Route::get('/departments', [DepartmentController::class, 'index']);
        Route::get('/designations', [DesignationController::class, 'index']);
        Route::get('/employees/managers', [EmployeeController::class, 'managers']);
        Route::get('/employees', [EmployeeController::class, 'index']);
        Route::post('/employees', [EmployeeController::class, 'store']);
        Route::get('/employees/{employee}/attendances', [AttendanceController::class, 'forEmployee']);
        Route::get('/employees/{employee}', [EmployeeController::class, 'show']);
        Route::get('/employees/{employee}/documents', [EmployeeDocumentController::class, 'index']);
        Route::post('/employees/{employee}/documents', [EmployeeDocumentController::class, 'store']);
        Route::get('/employees/{employee}/documents/{document}/download', [EmployeeDocumentController::class, 'download']);
        Route::delete('/employees/{employee}/documents/{document}', [EmployeeDocumentController::class, 'destroy']);
        Route::post('/employees/{employee}', [EmployeeController::class, 'update']);
        Route::put('/employees/{employee}', [EmployeeController::class, 'update']);

        Route::post('/attendances', [AttendanceController::class, 'store']);
        Route::put('/attendances/{attendance}', [AttendanceController::class, 'update']);
        Route::delete('/attendances/{attendance}', [AttendanceController::class, 'destroy']);

        Route::post('/zkteco/sync-attendance', [ZktecoController::class, 'sync']);

        Route::get('/leave-types', [LeaveTypeController::class, 'index']);
        Route::get('/employees/{employee}/leave-requests', [LeaveRequestController::class, 'index']);
        Route::get('/leave-requests/pending', [LeaveRequestController::class, 'pending']);
        Route::post('/leave-requests/{leaveRequest}/approve', [LeaveRequestController::class, 'approve']);
        Route::post('/leave-requests/{leaveRequest}/reject', [LeaveRequestController::class, 'reject']);
        Route::post('/leave-requests', [LeaveRequestController::class, 'store']);

        Route::get('/payroll/runs', [PayrollRunController::class, 'index']);
        Route::post('/payroll/runs', [PayrollRunController::class, 'store']);
        Route::get('/payroll/runs/{payrollRun}', [PayrollRunController::class, 'show']);
        Route::put('/payroll/runs/{payrollRun}/items/{payrollItem}', [PayrollRunController::class, 'updateItem']);
        Route::post('/payroll/runs/{payrollRun}/finalize', [PayrollRunController::class, 'finalize']);
        Route::get('/payroll/runs/{payrollRun}/export', [PayrollRunController::class, 'exportCsv']);
        Route::get('/my/payslips', [EssPayslipController::class, 'index']);
        Route::get('/my/payslips/{payrollItem}/download', [EssPayslipController::class, 'download']);

        Route::get('/job-positions', [JobPositionController::class, 'index']);
        Route::post('/job-positions', [JobPositionController::class, 'store']);
        Route::get('/job-positions/{jobPosition}', [JobPositionController::class, 'show']);
        Route::put('/job-positions/{jobPosition}', [JobPositionController::class, 'update']);
        Route::get('/job-positions/{jobPosition}/applications', [JobApplicationController::class, 'index']);
        Route::post('/job-positions/{jobPosition}/applications', [JobApplicationController::class, 'store']);
        Route::put('/job-positions/{jobPosition}/applications/{jobApplication}', [JobApplicationController::class, 'update']);
        Route::get('/job-positions/{jobPosition}/applications/{jobApplication}/interviews', [ApplicationInterviewController::class, 'index']);
        Route::post('/job-positions/{jobPosition}/applications/{jobApplication}/interviews', [ApplicationInterviewController::class, 'store']);
    });

    Route::get('/dashboard/{any?}', function () {
        return view('dashboard.app');
    })->where('any', '.*')->name('dashboard');
});
