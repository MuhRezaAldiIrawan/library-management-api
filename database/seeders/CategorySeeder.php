<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Fiction',
                'description' => 'Fictional stories and novels'
            ],
            [
                'name' => 'Non-Fiction',
                'description' => 'Real-world subjects and factual content'
            ],
            [
                'name' => 'Science',
                'description' => 'Scientific books and research'
            ],
            [
                'name' => 'Technology',
                'description' => 'Technology and programming books'
            ],
            [
                'name' => 'History',
                'description' => 'Historical books and biographies'
            ],
            [
                'name' => 'Self-Help',
                'description' => 'Personal development and motivation'
            ],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
