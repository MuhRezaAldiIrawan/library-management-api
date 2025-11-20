<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Book;
use App\Models\Category;
use App\Models\Borrow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class BorrowTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function guest_cannot_access_borrows()
    {
        $response = $this->getJson('/api/v1/borrows');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthenticated'
            ]);
    }

    /** @test */
    public function authenticated_user_can_get_their_borrowed_books()
    {
        $auth = $this->authenticateUser();
        $category = Category::factory()->create();
        $book = Book::factory()->create(['category_id' => $category->id]);

        Borrow::factory()->create([
            'user_id' => $auth['user']->id,
            'book_id' => $book->id
        ]);

        $response = $this->getJson('/api/v1/borrows', $this->getAuthHeaders($auth['token']));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'book', 'borrow_date', 'due_date', 'status']
                ],
                'meta'
            ]);
    }

    /** @test */
    public function authenticated_user_can_filter_active_borrows()
    {
        $auth = $this->authenticateUser();
        $category = Category::factory()->create();
        $book = Book::factory()->create(['category_id' => $category->id]);

        Borrow::factory()->create([
            'user_id' => $auth['user']->id,
            'book_id' => $book->id,
            'return_date' => null
        ]);

        $response = $this->getJson('/api/v1/borrows?status=active', $this->getAuthHeaders($auth['token']));

        $response->assertStatus(200);
        $this->assertGreaterThan(0, count($response->json('data')));
    }

    /** @test */
    public function authenticated_user_can_borrow_available_book()
    {
        $auth = $this->authenticateUser();
        $category = Category::factory()->create();
        $book = Book::factory()->create([
            'category_id' => $category->id,
            'stock' => 5
        ]);

        $response = $this->postJson('/api/v1/borrows', [
            'book_id' => $book->id,
            'borrow_days' => 14
        ], $this->getAuthHeaders($auth['token']));

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Book borrowed successfully'
            ]);

        $this->assertDatabaseHas('borrows', [
            'user_id' => $auth['user']->id,
            'book_id' => $book->id,
            'return_date' => null
        ]);

        // Check stock decreased
        $this->assertEquals(4, $book->fresh()->stock);
    }

    /** @test */
    public function cannot_borrow_book_with_zero_stock()
    {
        $auth = $this->authenticateUser();
        $category = Category::factory()->create();
        $book = Book::factory()->create([
            'category_id' => $category->id,
            'stock' => 0
        ]);

        $response = $this->postJson('/api/v1/borrows', [
            'book_id' => $book->id,
            'borrow_days' => 14
        ], $this->getAuthHeaders($auth['token']));

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Book is not available for borrowing'
            ]);
    }

    /** @test */
    public function cannot_borrow_same_book_twice_without_returning()
    {
        $auth = $this->authenticateUser();
        $category = Category::factory()->create();
        $book = Book::factory()->create([
            'category_id' => $category->id,
            'stock' => 5
        ]);

        // First borrow
        Borrow::factory()->create([
            'user_id' => $auth['user']->id,
            'book_id' => $book->id,
            'return_date' => null
        ]);

        // Try to borrow again
        $response = $this->postJson('/api/v1/borrows', [
            'book_id' => $book->id,
            'borrow_days' => 14
        ], $this->getAuthHeaders($auth['token']));

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'You have already borrowed this book'
            ]);
    }

    /** @test */
    public function book_id_is_required_to_borrow()
    {
        $auth = $this->authenticateUser();

        $response = $this->postJson('/api/v1/borrows', [
            'borrow_days' => 14
        ], $this->getAuthHeaders($auth['token']));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['book_id']);
    }

    /** @test */
    public function authenticated_user_can_return_borrowed_book()
    {
        $auth = $this->authenticateUser();
        $category = Category::factory()->create();
        $book = Book::factory()->create([
            'category_id' => $category->id,
            'stock' => 4
        ]);

        $borrow = Borrow::factory()->create([
            'user_id' => $auth['user']->id,
            'book_id' => $book->id,
            'return_date' => null
        ]);

        $response = $this->postJson('/api/v1/borrows/return', [
            'borrow_id' => $borrow->id
        ], $this->getAuthHeaders($auth['token']));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Book returned successfully'
            ]);

        $this->assertNotNull($borrow->fresh()->return_date);

        // Check stock increased
        $this->assertEquals(5, $book->fresh()->stock);
    }

    /** @test */
    public function cannot_return_already_returned_book()
    {
        $auth = $this->authenticateUser();
        $category = Category::factory()->create();
        $book = Book::factory()->create(['category_id' => $category->id]);

        $borrow = Borrow::factory()->create([
            'user_id' => $auth['user']->id,
            'book_id' => $book->id,
            'return_date' => Carbon::now()
        ]);

        $response = $this->postJson('/api/v1/borrows/return', [
            'borrow_id' => $borrow->id
        ], $this->getAuthHeaders($auth['token']));

        $response->assertStatus(404);
    }

    /** @test */
    public function user_cannot_return_other_users_borrowed_book()
    {
        $auth = $this->authenticateUser();
        $otherUser = \App\Models\User::factory()->create();

        $category = Category::factory()->create();
        $book = Book::factory()->create(['category_id' => $category->id]);

        $borrow = Borrow::factory()->create([
            'user_id' => $otherUser->id,
            'book_id' => $book->id,
            'return_date' => null
        ]);

        $response = $this->postJson('/api/v1/borrows/return', [
            'borrow_id' => $borrow->id
        ], $this->getAuthHeaders($auth['token']));

        $response->assertStatus(404);
    }

    /** @test */
    public function authenticated_user_can_get_single_borrow()
    {
        $auth = $this->authenticateUser();
        $category = Category::factory()->create();
        $book = Book::factory()->create(['category_id' => $category->id]);

        $borrow = Borrow::factory()->create([
            'user_id' => $auth['user']->id,
            'book_id' => $book->id
        ]);

        $response = $this->getJson("/api/v1/borrows/{$borrow->id}", $this->getAuthHeaders($auth['token']));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $borrow->id
                ]
            ]);
    }

    /** @test */
    public function user_cannot_access_other_users_borrow_record()
    {
        $auth = $this->authenticateUser();
        $otherUser = \App\Models\User::factory()->create();

        $category = Category::factory()->create();
        $book = Book::factory()->create(['category_id' => $category->id]);

        $borrow = Borrow::factory()->create([
            'user_id' => $otherUser->id,
            'book_id' => $book->id
        ]);

        $response = $this->getJson("/api/v1/borrows/{$borrow->id}", $this->getAuthHeaders($auth['token']));

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized access'
            ]);
    }
}
