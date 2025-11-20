<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Borrow extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'book_id',
        'borrow_date',
        'due_date',
        'return_date',
    ];

    protected $casts = [
        'borrow_date' => 'date',
        'due_date' => 'date',
        'return_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationship with user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship with book
     */
    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    /**
     * Check if borrow is overdue
     */
    public function isOverdue(): bool
    {
        if ($this->return_date) {
            return false;
        }

        return Carbon::now()->isAfter($this->due_date);
    }

    /**
     * Check if borrow is active (not returned)
     */
    public function isActive(): bool
    {
        return is_null($this->return_date);
    }

    /**
     * Scope for active borrows
     */
    public function scopeActive($query)
    {
        return $query->whereNull('return_date');
    }

    /**
     * Scope for returned borrows
     */
    public function scopeReturned($query)
    {
        return $query->whereNotNull('return_date');
    }
}
