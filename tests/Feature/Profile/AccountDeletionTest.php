<?php

declare(strict_types=1);

namespace Tests\Feature\Profile;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AccountDeletionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_delete_own_account_with_correct_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('secret123')]);

        $response = $this->actingAs($user)->deleteJson('/api/users/profile', [
            'password' => 'secret123',
        ]);

        $response->assertNoContent();
        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    /** @test */
    public function account_deletion_revokes_all_tokens(): void
    {
        $user = User::factory()->create(['password' => Hash::make('secret123')]);
        $user->createToken('test-token');

        $this->actingAs($user)->deleteJson('/api/users/profile', [
            'password' => 'secret123',
        ]);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
        ]);
    }

    /** @test */
    public function account_deletion_fails_with_wrong_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('secret123')]);

        $response = $this->actingAs($user)->deleteJson('/api/users/profile', [
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['password']);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'deleted_at' => null]);
    }

    /** @test */
    public function account_deletion_deletes_avatar_from_storage(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('avatars/1/photo.jpg', 'fake');
        $user = User::factory()->create([
            'password' => Hash::make('secret123'),
            'avatar' => 'avatars/1/photo.jpg',
        ]);

        $this->actingAs($user)->deleteJson('/api/users/profile', [
            'password' => 'secret123',
        ]);

        Storage::disk('public')->assertMissing('avatars/1/photo.jpg');
    }

    /** @test */
    public function unauthenticated_user_cannot_call_delete_account(): void
    {
        $response = $this->deleteJson('/api/users/profile', [
            'password' => 'whatever',
        ]);

        $response->assertUnauthorized();
    }
}
