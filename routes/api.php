<?php
use App\Http\Controllers\API\RegisterController;
use App\Http\Controllers\API\ProductController;
use App\Http\Controllers\API\CategoryController;
use App\Http\Controllers\API\TaskController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::controller(RegisterController::class)->group(function () {
    Route::post('register', 'register');
    Route::post('/login', function (Request $request) {
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response(['message' => __('auth.failed')], 422);
        }
        $token = auth()->user()->createToken('client');
        return ['token' => $token->plainTextToken];
    });
});



Route::middleware('auth:sanctum')->group(function () {

    Route::resource('categories', CategoryController::class);
    Route::resource('tasks', TaskController::class);
    Route::post('tasks/update/{id}', [TaskController::class, 'updateTask'])->name('tasks.updateTask');
    Route::get('tasks/export', [TaskController::class, 'exportTasks']);
    Route::get('tasks/search', [TaskController::class, 'search']); // Search tasks
    Route::post('/logout', function (Request $request) {
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Successfully logged out']);
    });
});
