<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Book;
use App\Models\Category;

class BookSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $books = [
            [
                'category' => 'Fiction',
                'title' => 'The Great Gatsby',
                'author' => 'F. Scott Fitzgerald',
                'isbn' => '9780743273565',
                'publisher' => 'Scribner',
                'publication_year' => 1925,
                'stock' => 5,
                'description' => 'A classic American novel set in the Jazz Age'
            ],
            [
                'category' => 'Technology',
                'title' => 'Clean Code',
                'author' => 'Robert C. Martin',
                'isbn' => '9780132350884',
                'publisher' => 'Prentice Hall',
                'publication_year' => 2008,
                'stock' => 10,
                'description' => 'A handbook of agile software craftsmanship'
            ],
            [
                'category' => 'Science',
                'title' => 'A Brief History of Time',
                'author' => 'Stephen Hawking',
                'isbn' => '9780553380163',
                'publisher' => 'Bantam',
                'publication_year' => 1988,
                'stock' => 3,
                'description' => 'From the Big Bang to black holes'
            ],
            [
                'category' => 'Self-Help',
                'title' => 'Atomic Habits',
                'author' => 'James Clear',
                'isbn' => '9780735211292',
                'publisher' => 'Avery',
                'publication_year' => 2018,
                'stock' => 8,
                'description' => 'An easy & proven way to build good habits'
            ],
        ];

        foreach ($books as $bookData) {
            $category = Category::where('name', $bookData['category'])->first();

            if ($category) {
                Book::create([
                    'category_id' => $category->id,
                    'title' => $bookData['title'],
                    'author' => $bookData['author'],
                    'isbn' => $bookData['isbn'],
                    'publisher' => $bookData['publisher'],
                    'publication_year' => $bookData['publication_year'],
                    'stock' => $bookData['stock'],
                    'description' => $bookData['description'],
                ]);
            }
        }
    }
}
