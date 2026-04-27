<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Zkteco\ZktecoAttendanceSyncService;
use Illuminate\Http\Request;
use Throwable;

class ZktecoController extends Controller
{
    public function sync(Request $request, ZktecoAttendanceSyncService $sync)
    {
        abort_unless($request->user()?->can('hr.attendance.manage'), 403);

        try {
            $result = $sync->sync();
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => $e->getMessage(),
            ], 503);
        }

        return response()->json($result);
    }
}
