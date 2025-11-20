<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Book\StoreBookRequest;
use App\Http\Requests\Book\UpdateBookRequest;
use App\Http\Resources\Book\BookResource;
use App\Models\Book;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookController extends Controller
{
    /**
     * Display a listing of books
     */
    public function index(Request $request): JsonResponse
    {
        $query = Book::with(['category']);

        // Filter by category
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Search by title or author
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('author', 'like', "%{$search}%")
                  ->orWhere('isbn', 'like', "%{$search}%");
            });
        }

        $books = $query->paginate(15);

        return response()->json([
            'success' => true,
            'data' => BookResource::collection($books),
            'meta' => [
                'current_page' => $books->currentPage(),
                'last_page' => $books->lastPage(),
                'per_page' => $books->perPage(),
                'total' => $books->total(),
            ]
        ]);
    }

    /**
     * Store a newly created book
     */
    public function store(StoreBookRequest $request): JsonResponse
    {
        $book = Book::create($request->validated());
        $book->load('category');

        return response()->json([
            'success' => true,
            'message' => 'Book created successfully',
            'data' => new BookResource($book)
        ], 201);
    }

    /**
     * Display the specified book
     */
    public function show(Book $book): JsonResponse
    {
        $book->load('category');

        return response()->json([
            'success' => true,
            'data' => new BookResource($book)
        ]);
    }

    /**
     * Update the specified book
     */
    public function update(UpdateBookRequest $request, Book $book): JsonResponse
    {
        $book->update($request->validated());
        $book->load('category');

        return response()->json([
            'success' => true,
            'message' => 'Book updated successfully',
            'data' => new BookResource($book)
        ]);
    }

    /**
     * Remove the specified book
     */
    public function destroy(Book $book): JsonResponse
    {
        // Check if book is currently borrowed
        $activeBorrows = $book->borrows()->whereNull('return_date')->count();

        if ($activeBorrows > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete book that is currently borrowed'
            ], 422);
        }

        $book->delete();

        return response()->json([
            'success' => true,
            'message' => 'Book deleted successfully'
        ]);
    }
}
