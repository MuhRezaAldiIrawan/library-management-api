<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Category;
use App\Models\Book;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function guest_cannot_access_categories()
    {
        $response = $this->getJson('/api/v1/categories');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthenticated'
            ]);
    }

    /** @test */
    public function authenticated_user_can_get_all_categories()
    {
        $auth = $this->authenticateUser();
        Category::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/categories', $this->getAuthHeaders($auth['token']));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'name', 'description', 'created_at', 'updated_at']
                ],
                'meta'
            ]);
    }

    /** @test */
    public function authenticated_user_can_create_category()
    {
        $auth = $this->authenticateUser();

        $response = $this->postJson('/api/v1/categories', [
            'name' => 'Fiction',
            'description' => 'Fictional books'
        ], $this->getAuthHeaders($auth['token']));

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Category created successfully',
                'data' => [
                    'name' => 'Fiction',
                    'description' => 'Fictional books'
                ]
            ]);

        $this->assertDatabaseHas('categories', [
            'name' => 'Fiction'
        ]);
    }

    /** @test */
    public function category_name_is_required()
    {
        $auth = $this->authenticateUser();

        $response = $this->postJson('/api/v1/categories', [
            'description' => 'Test description'
        ], $this->getAuthHeaders($auth['token']));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    /** @test */
    public function authenticated_user_can_get_single_category()
    {
        $auth = $this->authenticateUser();
        $category = Category::factory()->create();

        $response = $this->getJson("/api/v1/categories/{$category->id}", $this->getAuthHeaders($auth['token']));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $category->id,
                    'name' => $category->name
                ]
            ]);
    }

    /** @test */
    public function returns_404_for_non_existent_category()
    {
        $auth = $this->authenticateUser();

        $response = $this->getJson('/api/v1/categories/999', $this->getAuthHeaders($auth['token']));

        $response->assertStatus(404)
            ->assertJson([
                'success' => false
            ]);
    }

    /** @test */
    public function authenticated_user_can_update_category()
    {
        $auth = $this->authenticateUser();
        $category = Category::factory()->create();

        $response = $this->putJson("/api/v1/categories/{$category->id}", [
            'name' => 'Updated Fiction',
            'description' => 'Updated description'
        ], $this->getAuthHeaders($auth['token']));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Category updated successfully',
                'data' => [
                    'name' => 'Updated Fiction'
                ]
            ]);

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Updated Fiction'
        ]);
    }

    /** @test */
    public function authenticated_user_can_delete_category_without_books()
    {
        $auth = $this->authenticateUser();
        $category = Category::factory()->create();

        $response = $this->deleteJson("/api/v1/categories/{$category->id}", [], $this->getAuthHeaders($auth['token']));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Category deleted successfully'
            ]);

        $this->assertSoftDeleted('categories', [
            'id' => $category->id
        ]);
    }

    /** @test */
    public function cannot_delete_category_with_existing_books()
    {
        $auth = $this->authenticateUser();
        $category = Category::factory()->create();
        Book::factory()->create(['category_id' => $category->id]);

        $response = $this->deleteJson("/api/v1/categories/{$category->id}", [], $this->getAuthHeaders($auth['token']));

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Cannot delete category with existing books'
            ]);

        $this->assertDatabaseHas('categories', [
            'id' => $category->id
        ]);
    }
}
