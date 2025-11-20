<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Borrow\BorrowBookRequest;
use App\Http\Requests\Borrow\ReturnBookRequest;
use App\Http\Resources\Borrow\BorrowResource;
use App\Models\Book;
use App\Models\Borrow;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BorrowController extends Controller
{
    /**
     * Display user's borrowed books
     */
    public function index(Request $request): JsonResponse
    {
        $query = Borrow::with(['book.category', 'user'])
            ->where('user_id', auth()->id());

        // Filter by status
        if ($request->has('status')) {
            if ($request->status === 'active') {
                $query->active();
            } elseif ($request->status === 'returned') {
                $query->returned();
            }
        }

        $borrows = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json([
            'success' => true,
            'data' => BorrowResource::collection($borrows),
            'meta' => [
                'current_page' => $borrows->currentPage(),
                'last_page' => $borrows->lastPage(),
                'per_page' => $borrows->perPage(),
                'total' => $borrows->total(),
            ]
        ]);
    }

    /**
     * Borrow a book
     */
    public function borrow(BorrowBookRequest $request): JsonResponse
    {
        return DB::transaction(function () use ($request) {
            $book = Book::lockForUpdate()->findOrFail($request->book_id);

            // Check if book is available
            if (!$book->isAvailable()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Book is not available for borrowing'
                ], 422);
            }

            // Check if user already borrowed this book and hasn't returned it
            $activeBorrow = Borrow::where('user_id', auth()->id())
                ->where('book_id', $book->id)
                ->active()
                ->exists();

            if ($activeBorrow) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have already borrowed this book'
                ], 422);
            }

            // Create borrow record
            $borrow = Borrow::create([
                'user_id' => auth()->id(),
                'book_id' => $book->id,
                'borrow_date' => Carbon::now(),
                'due_date' => Carbon::now()->addDays($request->borrow_days ?? 14),
            ]);

            // Decrease book stock
            $book->decreaseStock();

            $borrow->load(['book.category', 'user']);

            return response()->json([
                'success' => true,
                'message' => 'Book borrowed successfully',
                'data' => new BorrowResource($borrow)
            ], 201);
        });
    }

    /**
     * Return a borrowed book
     */
    public function return(ReturnBookRequest $request): JsonResponse
    {
        return DB::transaction(function () use ($request) {
            $borrow = Borrow::with('book')
                ->where('user_id', auth()->id())
                ->where('id', $request->borrow_id)
                ->active()
                ->firstOrFail();

            // Update return date
            $borrow->update([
                'return_date' => Carbon::now()
            ]);

            // Increase book stock
            $borrow->book->increaseStock();

            $borrow->load(['book.category', 'user']);

            return response()->json([
                'success' => true,
                'message' => 'Book returned successfully',
                'data' => new BorrowResource($borrow)
            ]);
        });
    }

    /**
     * Display details of a specific borrow
     */
    public function show(Borrow $borrow): JsonResponse
    {
        // Check if borrow belongs to authenticated user
        if ($borrow->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        $borrow->load(['book.category', 'user']);

        return response()->json([
            'success' => true,
            'data' => new BorrowResource($borrow)
        ]);
    }
}
