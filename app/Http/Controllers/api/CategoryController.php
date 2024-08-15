<?php

namespace App\Http\Controllers\API;

use App\Models\Category;
use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use Validator;
use App\Http\Resources\CategoryResource;
use Illuminate\Http\JsonResponse;

class CategoryController extends BaseController
{

    public function index(): JsonResponse
    {
        $categories = Category::all();
        return response()->json([
            'success' => true,
            'data' => CategoryResource::collection($categories),
            'message' => 'Categories retrieved successfully.'
        ], 200);
    }

    public function store(Request $request): JsonResponse
    {
        $input = $request->all();

        $validator = Validator::make($input, [
            'name' => 'required',
            'description' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error.',
                'errors' => $validator->errors()
            ], 400); // 400 Bad Request
        }

        $category = Category::create($input);

        return response()->json([
            'success' => true,
            'data' => new CategoryResource($category),
            'message' => 'Product created successfully.'
        ], 201); 
    }

}
