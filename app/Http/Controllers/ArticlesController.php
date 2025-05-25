<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Category;
use Illuminate\Http\Request;
use App\Models\ArticleCategory;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Jobs\GenerateSlugFromTitle;
use App\Http\Controllers\Controller;
use App\Jobs\GenerateSummaryFromContent;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ArticlesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $userRole = auth()->user()->role;
        $query = Article::with(['categories:id,name','user:id,name']);

        if ($userRole === 'author') {
            $query->where('user_id', auth()->id());
        }

        $query->when($request->has('category'), function ($q) use ($request) {
            $q->whereHas('categories', function ($q2) use ($request) {
                $q2->whereIn('categories.id', (array) $request->category);
            });
        });

        $query->when($request->has('status'), function ($q) use ($request) {
            $q->where('status', $request->status);
        });

        $query->when($request->has('from_date') && $request->has('to_date'), function ($q) use ($request) {
            $q->whereBetween('created_at', [$request->from_date, $request->to_date]);
        });

        $articles = $query->get();

        return response()->json([
            'success' => true,
            'message' => 'Articles retrieved successfully.',
            'data' => $articles
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
                'title' => 'required|string|max:100',
                // 'slug' => 'required|string|max:100|unique:articles,slug',
                'content' => 'required|string',
                // 'summary' => 'required|string',
                'category' => 'required|array',
                'category.*' => 'integer|exists:categories,id',
                'status' => 'required|string|in:Draft,Published,Archived',
                'author' => 'required|integer|exists:users,id'
            ];

            $messages = [
                'title.required' => 'The title is required.',
                'title.string' => 'The title must be a string.',
                'title.max' => 'The title may not be greater than 100 characters.',
                // 'slug.required' => 'The slug is required.',
                // 'slug.string' => 'The slug must be a string.',
                // 'slug.max' => 'The slug may not be greater than 100 characters.',
                // 'slug.unique' => 'The slug has already been taken.',
                'content.required' => 'The content is required.',
                'content.string' => 'The content must be a string.',
                // 'summary.required' => 'The summary is required.',
                // 'summary.string' => 'The summary must be a string.',
                'category.required' => 'At least one category is required.',
                'category.array' => 'The category must be an array.',
                'category.*.integer' => 'Each category must be a valid ID.',
                'category.*.exists' => 'One or more selected categories are invalid.',
                'status.required' => 'The status is required.',
                'status.string' => 'The status must be a string.',
                'status.in' => 'The status must be one of the following: Draft, Published, Archived.',
                'author.required' => 'The author field is required.',
                'author.integer' => 'The author must be a valid user ID.',
                'author.exists' => 'The selected author does not exist.'
            ];


            $validator = Validator::make($request->all(), $rules, $messages);

            if($validator->fails()) {
                return response()->json(['status'=>'failed','message'=>'Oops! Few of the details seems to be incomplete.','errors' => $validator->errors()]);
            }

            $data = array(
                'title' => $request->title,
                'slug' => $request->slug,
                'content' => $request->content,
                'summary' => $request->summary,
                'status' => $request->status,
                'user_id' => $request->author,
            );

            $article = Article::create($data);
            $article_id = $article->id;

            $articleCategories = $request->category;
            $categoryData = [];
            foreach($articleCategories as $tag) {
                $categoryData[] = array(
                    'article' => $article_id,
                    'category' => $tag
                );
            }

            ArticleCategory::insert($categoryData);

            GenerateSlugFromTitle::dispatch($article);
            GenerateSummaryFromContent::dispatch($article);

            DB::commit();

            return response()->json([
                'status'=>'success',
                'message'=>'Article added successfully',
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
        // $article = Article::with(['categories', 'user'])->findOrFail($id);

        $article = Article::with(['categories:id,name','user:id,name'])->findOrFail($id);


        if($article) {
            return response()->json([
                'status' => 'success',
                'message' => 'Article fetched successfully.',
                'data' => $article
            ]);
        } else {
            return response()->json([
                'status' => 'failed',
                'message' => 'Article not found.'
            ], 404);
        }

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
            $article = Article::findOrFail($id);
            $articleCreatedBy = $article->user_id;

            if(auth()->user()->role === "author") {
                if(auth()->user()->id !== $articleCreatedBy){
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Article is not created by the user',
                    ]);
                }
            }

            $rules = [
                'title' => 'sometimes|string|max:100',
                // 'slug' => [
                //     'sometimes',
                //     'string',
                //     'max:100',
                //      Rule::unique('articles')->ignore($article->id),
                // ],
                'content' => 'sometimes|string',
                // 'summary' => 'sometimes|string',
                'category' => 'sometimes|array',
                'category.*' => 'integer|exists:categories,id',
                'status' => 'sometimes|string|in:Draft,Published,Archived',
                'author' => 'sometimes|integer|exists:users,id'
            ];

            $messages = [
                'title.string' => 'The title must be a string.',
                'title.max' => 'The title may not be greater than 100 characters.',
                // 'slug.string' => 'The slug must be a string.',
                // 'slug.max' => 'The slug may not be greater than 100 characters.',
                // 'slug.unique' => 'The slug has already been taken.',
                'content.string' => 'The content must be a string.',
                // 'summary.string' => 'The summary must be a string.',
                'category.array' => 'The category must be an array.',
                'category.*.integer' => 'Each category must be a valid ID.',
                'category.*.exists' => 'One or more selected categories are invalid.',
                'status.string' => 'The status must be a string.',
                'status.in' => 'The status must be one of the following: Draft, Published, Archived.',
                'author.integer' => 'The author must be a valid user ID.',
                'author.exists' => 'The selected author does not exist.'
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Oops! Few of the details seem to be incomplete.',
                    'errors' => $validator->errors()
                ]);
            }

            $data = $request->only([
                'title', 'slug', 'content', 'summary', 'status'
            ]);

            $data['user_id'] = $request->author;

            $article->update($data);

            if ($request->has('category')) {
                $article->categories()->sync($request->category);
            }

            DB::commit();

            if ($request->filled('title') && $request->filled('content')) {
                GenerateSlugFromTitle::dispatch($article);
                GenerateSummaryFromContent::dispatch($article);
            } elseif ($request->filled('title')) {
                GenerateSlugFromTitle::dispatch($article);
            } elseif ($request->filled('content')) {
                GenerateSummaryFromContent::dispatch($article);
            }


            return response()->json([
                'status' => 'success',
                'message' => 'Article updated successfully',
            ]);
        } catch (ModelNotFoundException $exception) {
            DB::rollback();
            return response()->json([
                'status' => 'failed',
                'message' => 'Article not found',
            ], 404);
        } catch (\Exception $exception) {
            DB::rollback();
            return response()->json([
                'status' => 'failed',
                'message' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        DB::beginTransaction();

        try {
            $article = Article::findOrFail($id);
            $articleCreatedBy = $article->user_id;

            if(auth()->user()->role === "author") {
                if(auth()->user()->id !== $articleCreatedBy){
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Article is not created by the user',
                    ]);
                }
            }

            $article->categories()->detach();

            $article->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Article deleted successfully',
            ]);
        } catch (ModelNotFoundException $exception) {
            DB::rollback();
            return response()->json([
                'status' => 'failed',
                'message' => 'Article not found',
            ], 404);
        } catch (\Exception $exception) {
            DB::rollback();
            return response()->json([
                'status' => 'failed',
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
