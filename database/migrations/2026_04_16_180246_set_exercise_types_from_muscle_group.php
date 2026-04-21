<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Kardio pēc muscle_group
        DB::table('exercises')
            ->where('muscle_group', 'Kardio')
            ->update(['type' => 'cardio']);

        // Visi pārējie = strength
        DB::table('exercises')
            ->where('muscle_group', '!=', 'Kardio')
            ->update(['type' => 'strength']);
    }

    public function down(): void
    {
        DB::table('exercises')->update(['type' => 'strength']);
    }
};
