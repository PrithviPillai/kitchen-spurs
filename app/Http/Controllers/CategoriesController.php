<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CategoriesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = Category::all();

        return response()->json([
            'success' => true,
            'message' => 'Categories retrieved successfully.',
            'data' => $categories
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $rules = [
                'categoryName' => 'required|max:100',
            ];

            $messages = [
                'categoryName.required' => 'Category Name is required',
                'categoryName.max' => 'Max character allowed 100',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if($validator->fails()) {
                return response()->json([
                    'status'=>'failed',
                    'message'=>'Oops! Few of the details seems to be incomplete.',
                    'errors' => $validator->errors()
                ]);
            }

            Category::create(['name' => $request->categoryName]);

            DB::commit();

            return response()->json([
                'status'=>'success',
                'message'=>'Category added successfully',
            ]);
        } catch (ModelNotFoundException $exception) {
            DB::rollback();
            $message = "Something went wrong! Please try again later";
            return response()->json(['status'=>'failed','message'=>$message]);
        } catch (\Exception $exception) {
            DB::rollback();
            $message = $exception->getMessage();
            return response()->json(['status'=>'failed','message'=>$message]);
        } catch (\Throwable $exception) {
            DB::rollback();
            $message = $exception->getMessage();
            return response()->json(['status'=>'failed','message'=>$message]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $data = category::findOrFail($id);

        if(!$data) {
            return response()->json([
                'status' => 'error',
                'message' => 'Category not found',
                'data' => ""
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => "Category found",
            'data' => $data,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        DB::beginTransaction();
        try {
            $rules = [
                'categoryName' => 'required|max:100',
            ];

            $messages = [
                'categoryName.required' => 'Category Name is required',
                'categoryName.max' => 'Max character allowed 100',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if($validator->fails()) {
                return response()->json(['status'=>'failed','message'=>'Oops! Few of the details seems to be incomplete.','errors' => $validator->errors()]);
            }

            $categoryName = $request->categoryName;
            $category = Category::find($id);
            if($category) {
                $category->update([
                    'name' => $categoryName
                ]);
            } else {
                DB::rollBack();
                return response()->json(['status'=>'failed','message'=>'Category not found'], 404);
            }

            DB::commit();
            return response()->json(['status'=>'success', 'message'=> 'Category updated successfully']);
        } catch (ModelNotFoundException $exception) {
            DB::rollback();
            // $message = $this->modelNotFoundException($exception->getModel());
            $message = "Something went wrong! Please try again later";
            return response()->json(['status'=>'failed','message'=>$message]);
        } catch (\Exception $exception) {
            DB::rollback();
            $message = $exception->getMessage();
            return response()->json(['status'=>'failed','message'=>$message]);
        } catch (\Throwable $exception) {
            DB::rollback();
            $message = $exception->getMessage();
            return response()->json(['status'=>'failed','message'=>$message]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        DB::beginTransaction();
        try{
            $category = Category::find($id);

            if($category) {
                $category->delete();
                DB::commit();
                return response()->json(['status'=>'success', 'message'=>'Category deleted successfully']);
            } else {
                DB::rollback();
                $message = "Category not found";
                return response()->json(['status'=>'failed','message'=>$message]);
            }
        } catch (ModelNotFoundException $exception) {
            DB::rollback();
            $message = "Something went wrong! Please try again later";
            return response()->json(['status'=>'failed','message'=>$message]);
        } catch (\Exception $exception) {
            DB::rollback();
            $message = $exception->getMessage();
            return response()->json(['status'=>'failed','message'=>$message]);
        } catch (\Throwable $exception) {
            DB::rollback();
            $message = $exception->getMessage();
            return response()->json(['status'=>'failed','message'=>$message]);
        }
    }
}
