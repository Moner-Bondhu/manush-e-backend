<?php

use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $parent = User::where('phone_number', '+8801700000011')->where('experiment_tag', 'demo_school')->first();
        if ($parent) {
            $parent->update(['name' => 'Nadia Rahman']);
            Profile::where('user_id', $parent->id)->where('type', 'parent')->update(['full_name' => 'Nadia Rahman']);
            Profile::where('user_id', $parent->id)->where('type', 'child')->update(['full_name' => 'Ayaan Rahman']);
        }

        User::where('phone_number', '+8801700000021')->where('experiment_tag', 'demo_school')->update(['name' => 'Ayaan Rahman']);
    }

    public function down(): void
    {
        $parent = User::where('phone_number', '+8801700000011')->where('experiment_tag', 'demo_school')->first();
        if ($parent) {
            $parent->update(['name' => 'Maya Sen']);
            Profile::where('user_id', $parent->id)->where('type', 'parent')->update(['full_name' => 'Maya Sen']);
            Profile::where('user_id', $parent->id)->where('type', 'child')->update(['full_name' => 'Aarav Sen']);
        }

        User::where('phone_number', '+8801700000021')->where('experiment_tag', 'demo_school')->update(['name' => 'Aarav Sen']);
    }
};
