<?php

namespace App\Http\Controllers\API;

use App\Http\Resources\TaskResource;
use App\Models\Task;
use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use Validator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TaskController extends BaseController
{
    public function index(Request $request)
    {
        $query = Task::query();

        if ($request->has('search')) {
            $query->where('taskname', 'LIKE', '%' . $request->search . '%')
                ->orWhere('description', 'LIKE', '%' . $request->search . '%');
        }

        if ($request->has('category')) {
            $query->where('category_id', $request->category);
        }

        $tasks = $query->get();

        return response()->json([
            'data' => $tasks
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        // Validate the input
        $validator = Validator::make($request->all(), [
            'taskname' => 'required|string|max:255',
            'description' => 'nullable|string',
            'startdate' => 'required|date',
            'enddate' => 'required|date',
            'category' => 'sometimes|required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error.',
                'errors' => $validator->errors()
            ], 400); // 400 Bad Request
        }

        // Prepare data for the task
        $input = $request->except('image');

        // Handle image upload
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imagePath = $image->store('images', 'public');
            $input['image'] = $imagePath;
        }

        // Create the task
        $task = Task::create($input);

        return response()->json([
            'success' => true,
            'data' => new TaskResource($task),
            'message' => 'Task created successfully.'
        ], 201); // 201 Created
    }

    public function show($id): JsonResponse
    {
        $task = Task::find($id);

        if (is_null($task)) {
            return response()->json([
                'success' => false,
                'message' => 'Task not found.'
            ], 404); // 404 Not Found
        }

        return response()->json([
            'success' => true,
            'data' => new TaskResource($task),
            'message' => 'Task retrieved successfully.'
        ], 200); // 200 OK
    }

    public function updateTask(Request $request, $id): JsonResponse
    {
        $task = Task::find($id);

        if (!$task) {
            return response()->json([
                'success' => false,
                'message' => 'Task not found.'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'taskname' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'startdate' => 'sometimes|required|date',
            'enddate' => 'sometimes|required|date',
            'category' => 'sometimes|required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error.',
                'errors' => $validator->errors()
            ], 400);
        }

        $input = $request->except('image');

        if ($request->hasFile('image')) {
            if ($task->image) {
                Storage::disk('public')->delete($task->image);
            }

            $image = $request->file('image');
            $imagePath = $image->store('images', 'public');
            $input['image'] = $imagePath;
        }

        $task->update($input);

        return response()->json([
            'success' => true,
            'data' => new TaskResource($task),
            'message' => 'Task updated successfully.'
        ], 200);
    }
    public function destroy(Task $task): JsonResponse
    {
        $task->delete();

        return response()->json([
            'success' => true,
            'message' => 'Task deleted successfully.'
        ], 200); // 200 OK
    }

    public function exportTasks()
    {
        $tasks = Task::all();


        if ($tasks->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Noo tasks found.',
            ], 404);
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Add column headers
        $sheet->setCellValue('A1', 'Task Name');
        $sheet->setCellValue('B1', 'Description');
        $sheet->setCellValue('C1', 'Start Date');
        $sheet->setCellValue('D1', 'End Date');
        $sheet->setCellValue('E1', 'Category Name');

        // Add tasks data
        $row = 2;
        foreach ($tasks as $task) {
            $sheet->setCellValue('A' . $row, $task->taskname);
            $sheet->setCellValue('B' . $row, $task->description);
            $sheet->setCellValue('C' . $row, $task->startdate);
            $sheet->setCellValue('D' . $row, $task->enddate);
            $sheet->setCellValue('E' . $row, $task->category->name);
            $row++;
        }

        // Create a StreamedResponse to send the file
        $response = new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        });

        // Set headers to download the file
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="tasks.xlsx"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }

}
