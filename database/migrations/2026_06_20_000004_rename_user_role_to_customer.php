<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('roles')
            ->where('slug', 'user')
            ->update([
                'slug' => 'customer',
                'name' => 'Customer',
                'description' => 'Standard customer account.',
            ]);
    }

    public function down(): void
    {
        DB::table('roles')
            ->where('slug', 'customer')
            ->update([
                'slug' => 'user',
                'name' => 'User',
                'description' => 'Standard application user.',
            ]);
    }
};
