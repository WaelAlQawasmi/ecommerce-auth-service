<?php

namespace Tests\Unit\Repositories;

use App\Models\User;
use App\Repositories\Eloquent\UserRepository;
use Tests\TestCase;

class UserRepositoryTest extends TestCase
{
    private UserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new UserRepository;
    }

    public function test_paginate_returns_users_with_roles(): void
    {
        User::factory()->count(3)->create();

        $paginator = $this->repository->paginate(2);

        $this->assertCount(2, $paginator->items());
        $this->assertSame(3, $paginator->total());
        $this->assertTrue($paginator->items()[0]->relationLoaded('roles'));
    }

    public function test_search_by_email_returns_partial_matches(): void
    {
        User::factory()->create(['email' => 'alice@example.com']);
        User::factory()->create(['email' => 'bob@example.com']);
        User::factory()->create(['email' => 'alice.wonder@example.com']);

        $results = $this->repository->searchByEmail('alice');

        $this->assertCount(2, $results);
        $this->assertSame(['alice@example.com', 'alice.wonder@example.com'], $results->pluck('email')->all());
    }

    public function test_count_excludes_soft_deleted_users(): void
    {
        $active = User::factory()->create();
        User::factory()->create()->delete();

        $this->assertSame(1, $this->repository->count());
        $this->assertNotNull($active->fresh());
    }
}
