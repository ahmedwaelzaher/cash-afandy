<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('user_preferences')->orderBy('id')->chunkById(100, function ($preferences) {
            foreach ($preferences as $preference) {
                $selected = json_decode($preference->notification_types ?? 'null', true);
                if (is_array($selected) && ! array_is_list($selected)) continue;
                // General was the only available type before this storage change.
                $disabled = $selected === null ? [] : array_values(array_diff(['general'], $selected));
                DB::table('user_preferences')->where('id', $preference->id)
                    ->update(['notification_types' => json_encode(['disabled' => $disabled])]);
            }
        });
    }

    public function down(): void
    {
        DB::table('user_preferences')->orderBy('id')->chunkById(100, function ($preferences) {
            foreach ($preferences as $preference) {
                $types = json_decode($preference->notification_types ?? 'null', true);
                if (! is_array($types) || array_is_list($types)) continue;
                $selected = array_values(array_diff(['general'], $types['disabled'] ?? []));
                DB::table('user_preferences')->where('id', $preference->id)
                    ->update(['notification_types' => json_encode($selected)]);
            }
        });
    }
};
