<?php

namespace Tests\Feature\Api\V1;

use App\Enums\RoleSlug;
use App\Listeners\SendWelcomeEmail;
use App\Mail\WelcomeMail;
use App\Models\User;
use Database\Seeders\PassportClientSeeder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AuthRegistrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PassportClientSeeder::class);
    }

    public function test_registration_with_explicit_customer_role_succeeds(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Wael',
            'email' => 'wael@admin.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => RoleSlug::Customer->value,
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'wael@admin.com');
    }

    public function test_registration_assigns_customer_role(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Jane Customer',
            'email' => 'jane@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'jane@example.com')
            ->assertJsonPath('data.user.roles.0.slug', RoleSlug::Customer->value);

        $this->assertTrue(
            User::query()->where('email', 'jane@example.com')->first()?->hasRole(RoleSlug::Customer->value) ?? false
        );
    }

    public function test_registration_queues_welcome_email(): void
    {
        Queue::fake();

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Jane Customer',
            'email' => 'jane@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertCreated();

        Queue::assertPushed(SendWelcomeEmail::class);
    }

    public function test_welcome_email_is_sent_on_registration(): void
    {
        Mail::fake();

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Jane Customer',
            'email' => 'jane@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertCreated();

        Mail::assertSent(WelcomeMail::class, function (WelcomeMail $mail): bool {
            return $mail->hasTo('jane@example.com')
                && $mail->user->name === 'Jane Customer';
        });
    }
}
