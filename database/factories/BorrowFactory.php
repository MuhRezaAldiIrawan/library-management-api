<?php

namespace Database\Factories;

use App\Models\Borrow;
use App\Models\User;
use App\Models\Book;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Borrow>
 */
class BorrowFactory extends Factory
{
    protected $model = Borrow::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $borrowDate = Carbon::now()->subDays(fake()->numberBetween(1, 30));
        $dueDate = $borrowDate->copy()->addDays(14);

        return [
            'user_id' => User::factory(),
            'book_id' => Book::factory(),
            'borrow_date' => $borrowDate,
            'due_date' => $dueDate,
            'return_date' => null,
        ];
    }

    /**
     * Indicate that the borrow has been returned.
     */
    public function returned(): static
    {
        return $this->state(function (array $attributes) {
            $borrowDate = Carbon::parse($attributes['borrow_date']);
            return [
                'return_date' => $borrowDate->copy()->addDays(fake()->numberBetween(1, 14)),
            ];
        });
    }

    /**
     * Indicate that the borrow is overdue.
     */
    public function overdue(): static
    {
        return $this->state(function (array $attributes) {
            $borrowDate = Carbon::now()->subDays(20);
            return [
                'borrow_date' => $borrowDate,
                'due_date' => $borrowDate->copy()->addDays(14),
                'return_date' => null,
            ];
        });
    }
}
