<?php

declare(strict_types=1);

namespace Tests\Feature\Profile;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AvatarUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    /** @test */
    public function authenticated_user_can_upload_avatar(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/api/users/profile', [
            'nom' => $user->nom,
            'avatar' => UploadedFile::fake()->image('avatar.jpg', 100, 100),
        ]);

        $response->assertOk();
        $user->refresh();
        $this->assertNotNull($user->avatar);
        Storage::disk('public')->assertExists($user->avatar);
    }

    /** @test */
    public function avatar_upload_rejects_non_image_files(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/users/profile', [
            'nom' => $user->nom,
            'avatar' => UploadedFile::fake()->create('malware.exe', 100, 'application/octet-stream'),
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['avatar']);
    }

    /** @test */
    public function uploading_new_avatar_replaces_old_one(): void
    {
        Storage::disk('public')->put('avatars/1/old.jpg', 'fake');
        $user = User::factory()->create(['avatar' => 'avatars/1/old.jpg']);

        $this->actingAs($user)->post('/api/users/profile', [
            'nom' => $user->nom,
            'avatar' => UploadedFile::fake()->image('new-avatar.png', 100, 100),
        ]);

        Storage::disk('public')->assertMissing('avatars/1/old.jpg');
        $user->refresh();
        $this->assertNotEquals('avatars/1/old.jpg', $user->avatar);
    }

    /** @test */
    public function unauthenticated_user_cannot_upload_avatar(): void
    {
        $response = $this->postJson('/api/users/profile', [
            'avatar' => UploadedFile::fake()->image('avatar.jpg'),
        ]);

        $response->assertUnauthorized();
    }
}
