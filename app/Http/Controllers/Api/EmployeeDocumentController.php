<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EmployeeDocumentResource;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeDocumentController extends Controller
{
    public function index(Request $request, Employee $employee)
    {
        $this->authorizeDocuments($request);

        $rows = EmployeeDocument::query()
            ->where('employee_id', $employee->id)
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'data' => EmployeeDocumentResource::collection($rows)->resolve(),
        ]);
    }

    public function store(Request $request, Employee $employee)
    {
        $this->authorizeDocuments($request);

        $data = $request->validate([
            'category' => ['required', 'string', 'in:cv,contract,id_copy,medical,other'],
            'title' => ['nullable', 'string', 'max:200'],
            'files' => ['required', 'array', 'min:1', 'max:30'],
            'files.*' => ['file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx'],
        ]);

        $files = $request->file('files');
        $single = count($files) === 1;
        $title = $single ? ($data['title'] ?? null) : null;

        $rows = [];
        foreach ($files as $file) {
            $path = $file->store("employee-documents/{$employee->id}", 'local');

            $rows[] = EmployeeDocument::query()->create([
                'employee_id' => $employee->id,
                'category' => $data['category'],
                'title' => $title,
                'disk' => 'local',
                'path' => $path,
                'original_filename' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'size_bytes' => $file->getSize(),
                'uploaded_by_user_id' => $request->user()->id,
            ]);
        }

        $payload = array_map(
            fn (EmployeeDocument $row) => (new EmployeeDocumentResource($row))->resolve(),
            $rows
        );

        $count = count($payload);

        return response()->json([
            'message' => $count === 1 ? 'Document uploaded.' : "{$count} files uploaded.",
            'data' => $payload,
        ], 201);
    }

    public function download(Request $request, Employee $employee, EmployeeDocument $document): StreamedResponse|\Illuminate\Http\Response
    {
        $this->authorizeDocuments($request);
        abort_unless((int) $document->employee_id === (int) $employee->id, 404);

        if (! Storage::disk($document->disk)->exists($document->path)) {
            abort(404, 'File missing on server.');
        }

        return Storage::disk($document->disk)->download(
            $document->path,
            $document->original_filename ?? basename($document->path)
        );
    }

    public function destroy(Request $request, Employee $employee, EmployeeDocument $document)
    {
        $this->authorizeDocuments($request);
        abort_unless((int) $document->employee_id === (int) $employee->id, 404);

        if (Storage::disk($document->disk)->exists($document->path)) {
            Storage::disk($document->disk)->delete($document->path);
        }
        $document->delete();

        return response()->noContent();
    }

    private function authorizeDocuments(Request $request): void
    {
        $user = $request->user();
        abort_unless(
            $user && ($user->can('hr.documents.manage') || $user->can('hr.employees.manage')),
            403
        );
    }
}
