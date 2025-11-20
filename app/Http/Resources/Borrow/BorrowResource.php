<?php

namespace App\Http\Resources\Borrow;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BorrowResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ],
            'book' => [
                'id' => $this->book->id,
                'title' => $this->book->title,
                'author' => $this->book->author,
                'isbn' => $this->book->isbn,
                'category' => [
                    'id' => $this->book->category->id,
                    'name' => $this->book->category->name,
                ],
            ],
            'borrow_date' => $this->borrow_date?->format('Y-m-d'),
            'due_date' => $this->due_date?->format('Y-m-d'),
            'return_date' => $this->return_date?->format('Y-m-d'),
            'status' => $this->isActive() ? 'active' : 'returned',
            'is_overdue' => $this->isOverdue(),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
