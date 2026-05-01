<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use App\Models\User;
use App\Notifications\ActivityNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

class EmployeeController extends Controller
{
    public function managers(Request $request)
    {
        abort_unless($request->user()?->can('hr.employees.manage'), 403);

        $rows = Employee::query()
            ->where('status', 'active')
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'employee_code', 'profile_photo_path']);

        return response()->json([
            'data' => $rows->map(fn (Employee $e) => [
                'id' => $e->id,
                'full_name' => $e->full_name,
                'employee_code' => $e->employee_code,
                'profile_photo_url' => $e->profilePhotoUrl(),
            ]),
        ]);
    }

    public function index(Request $request)
    {
        abort_unless($request->user()?->can('hr.employees.manage'), 403);

        $query = Employee::query()
            ->with(['department', 'designation', 'manager'])
            ->orderByDesc('created_at');

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('employee_code', 'like', "%{$search}%")
                    ->orWhere('work_email', 'like', "%{$search}%")
                    ->orWhere('cnic', 'like', "%{$search}%");
            });
        }

        $perPage = min(max((int) $request->input('per_page', 15), 5), 50);

        return EmployeeResource::collection($query->paginate($perPage));
    }

    public function show(Request $request, Employee $employee)
    {
        abort_unless($request->user()?->can('hr.employees.manage'), 403);

        return new EmployeeResource(
            $employee->load(['department', 'designation', 'manager'])
        );
    }

    public function store(StoreEmployeeRequest $request)
    {
        $validated = $request->validated();
        unset($validated['profile_photo']);

        $createPortal = $request->boolean('create_portal_login');
        $portalEmail = $validated['portal_email'] ?? null;
        $portalPassword = $validated['portal_password'] ?? null;
        $portalRole = $validated['portal_role'] ?? null;

        unset(
            $validated['create_portal_login'],
            $validated['portal_email'],
            $validated['portal_password'],
            $validated['portal_password_confirmation'],
            $validated['portal_role'],
        );

        $validated['created_by_user_id'] = $request->user()->id;

        $employee = DB::transaction(function () use ($validated, $createPortal, $portalEmail, $portalPassword, $portalRole) {
            $userId = $validated['user_id'] ?? null;

            if ($createPortal && ! $userId) {
                $user = User::create([
                    'name' => $validated['full_name'],
                    'email' => $portalEmail,
                    'password' => Hash::make($portalPassword),
                    'email_verified_at' => now(),
                ]);
                $user->assignRole($portalRole);
                $validated['user_id'] = $user->id;
            }

            return Employee::create($validated);
        });

        if ($request->hasFile('profile_photo')) {
            $path = $request->file('profile_photo')->store('employees/profile-photos', 'public');
            $employee->update(['profile_photo_path' => $path]);
        }

        $actorId = $request->user()?->id;
        $recipients = User::query()->role(['admin', 'hr'])->get()->reject(fn ($u) => (int) $u->id === (int) $actorId);
        if ($employee->user_id) {
            $linked = User::query()->find($employee->user_id);
            if ($linked) {
                $recipients->push($linked);
            }
        }
        $recipients = $recipients->unique('id')->values();
        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new ActivityNotification([
                'title' => 'Employee added',
                'body' => $employee->full_name.' was added to the employee directory.',
                'link' => '/employees/'.$employee->id,
                'meta' => ['employee_id' => $employee->id],
            ]));
        }

        return (new EmployeeResource(
            $employee->fresh()->load(['department', 'designation', 'manager'])
        ))->response()->setStatusCode(201);
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee)
    {
        $data = $request->validated();
        unset($data['profile_photo']);

        if ($request->hasFile('profile_photo')) {
            if ($employee->profile_photo_path) {
                Storage::disk('public')->delete($employee->profile_photo_path);
            }
            $data['profile_photo_path'] = $request->file('profile_photo')->store('employees/profile-photos', 'public');
        }

        $employee->update($data);

        $actorId = $request->user()?->id;
        $recipients = User::query()->role(['admin', 'hr'])->get()->reject(fn ($u) => (int) $u->id === (int) $actorId);
        if ($employee->user_id) {
            $linked = User::query()->find($employee->user_id);
            if ($linked) {
                $recipients->push($linked);
            }
        }
        $recipients = $recipients->unique('id')->values();
        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new ActivityNotification([
                'title' => 'Employee updated',
                'body' => $employee->full_name.' profile was updated.',
                'link' => '/employees/'.$employee->id,
                'meta' => ['employee_id' => $employee->id],
            ]));
        }

        return new EmployeeResource(
            $employee->fresh()->load(['department', 'designation', 'manager'])
        );
    }
}
