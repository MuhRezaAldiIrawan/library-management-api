<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Book;
use App\Models\Category;
use App\Models\Borrow;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BookTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function guest_cannot_access_books()
    {
        $response = $this->getJson('/api/v1/books');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthenticated'
            ]);
    }

    /** @test */
    public function authenticated_user_can_get_all_books()
    {
        $auth = $this->authenticateUser();
        $category = Category::factory()->create();
        Book::factory()->count(3)->create(['category_id' => $category->id]);

        $response = $this->getJson('/api/v1/books', $this->getAuthHeaders($auth['token']));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'title', 'author', 'isbn', 'category', 'stock']
                ],
                'meta'
            ]);
    }

    /** @test */
    public function authenticated_user_can_filter_books_by_category()
    {
        $auth = $this->authenticateUser();
        $category1 = Category::factory()->create();
        $category2 = Category::factory()->create();

        Book::factory()->count(2)->create(['category_id' => $category1->id]);
        Book::factory()->create(['category_id' => $category2->id]);

        $response = $this->getJson("/api/v1/books?category_id={$category1->id}", $this->getAuthHeaders($auth['token']));

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    /** @test */
    public function authenticated_user_can_search_books()
    {
        $auth = $this->authenticateUser();
        $category = Category::factory()->create();

        Book::factory()->create([
            'category_id' => $category->id,
            'title' => 'Clean Code'
        ]);
        Book::factory()->create([
            'category_id' => $category->id,
            'title' => 'Design Patterns'
        ]);

        $response = $this->getJson('/api/v1/books?search=Clean', $this->getAuthHeaders($auth['token']));

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    /** @test */
    public function authenticated_user_can_create_book()
    {
        $auth = $this->authenticateUser();
        $category = Category::factory()->create();

        $response = $this->postJson('/api/v1/books', [
            'category_id' => $category->id,
            'title' => 'Clean Code',
            'author' => 'Robert C. Martin',
            'isbn' => '9780132350884',
            'publisher' => 'Prentice Hall',
            'publication_year' => 2008,
            'stock' => 10,
            'description' => 'A handbook of agile software craftsmanship'
        ], $this->getAuthHeaders($auth['token']));

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Book created successfully',
                'data' => [
                    'title' => 'Clean Code',
                    'author' => 'Robert C. Martin'
                ]
            ]);

        $this->assertDatabaseHas('books', [
            'title' => 'Clean Code',
            'isbn' => '9780132350884'
        ]);
    }

    /** @test */
    public function book_required_fields_validation()
    {
        $auth = $this->authenticateUser();

        $response = $this->postJson('/api/v1/books', [], $this->getAuthHeaders($auth['token']));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['category_id', 'title', 'author', 'isbn', 'stock']);
    }

    /** @test */
    public function authenticated_user_can_get_single_book()
    {
        $auth = $this->authenticateUser();
        $category = Category::factory()->create();
        $book = Book::factory()->create(['category_id' => $category->id]);

        $response = $this->getJson("/api/v1/books/{$book->id}", $this->getAuthHeaders($auth['token']));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $book->id,
                    'title' => $book->title
                ]
            ]);
    }

    /** @test */
    public function returns_404_for_non_existent_book()
    {
        $auth = $this->authenticateUser();

        $response = $this->getJson('/api/v1/books/999', $this->getAuthHeaders($auth['token']));

        $response->assertStatus(404)
            ->assertJson([
                'success' => false
            ]);
    }

    /** @test */
    public function authenticated_user_can_update_book()
    {
        $auth = $this->authenticateUser();
        $category = Category::factory()->create();
        $book = Book::factory()->create(['category_id' => $category->id]);

        $response = $this->putJson("/api/v1/books/{$book->id}", [
            'category_id' => $category->id,
            'title' => 'Updated Title',
            'author' => 'Updated Author',
            'isbn' => $book->isbn,
            'publisher' => 'Updated Publisher',
            'publication_year' => 2023,
            'stock' => 20,
            'description' => 'Updated description'
        ], $this->getAuthHeaders($auth['token']));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Book updated successfully',
                'data' => [
                    'title' => 'Updated Title'
                ]
            ]);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => 'Updated Title'
        ]);
    }

    /** @test */
    public function authenticated_user_can_delete_book_without_active_borrows()
    {
        $auth = $this->authenticateUser();
        $category = Category::factory()->create();
        $book = Book::factory()->create(['category_id' => $category->id]);

        $response = $this->deleteJson("/api/v1/books/{$book->id}", [], $this->getAuthHeaders($auth['token']));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Book deleted successfully'
            ]);

        $this->assertSoftDeleted('books', [
            'id' => $book->id
        ]);
    }

    /** @test */
    public function cannot_delete_book_with_active_borrows()
    {
        $auth = $this->authenticateUser();
        $category = Category::factory()->create();
        $book = Book::factory()->create(['category_id' => $category->id]);

        Borrow::factory()->create([
            'user_id' => $auth['user']->id,
            'book_id' => $book->id,
            'return_date' => null
        ]);

        $response = $this->deleteJson("/api/v1/books/{$book->id}", [], $this->getAuthHeaders($auth['token']));

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Cannot delete book that is currently borrowed'
            ]);
    }
}
