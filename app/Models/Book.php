<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Book extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id',
        'title',
        'author',
        'isbn',
        'publisher',
        'publication_year',
        'stock',
        'description',
    ];

    protected $casts = [
        'publication_year' => 'integer',
        'stock' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relationship with category
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Relationship with borrows
     */
    public function borrows()
    {
        return $this->hasMany(Borrow::class);
    }

    /**
     * Check if book is available
     */
    public function isAvailable(): bool
    {
        return $this->stock > 0;
    }

    /**
     * Decrease book stock
     */
    public function decreaseStock(int $quantity = 1): void
    {
        $this->decrement('stock', $quantity);
    }

    /**
     * Increase book stock
     */
    public function increaseStock(int $quantity = 1): void
    {
        $this->increment('stock', $quantity);
    }
}
