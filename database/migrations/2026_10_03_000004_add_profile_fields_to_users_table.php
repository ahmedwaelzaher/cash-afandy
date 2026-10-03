<?php

use App\Enums\Gender;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->after('email');
            $table->date('birthdate')->nullable()->after('phone');
            $table->enum('gender', Gender::cases())->nullable()->after('birthdate');
            $table->foreignId('country_id')->nullable()->after('gender')->constrained()->nullOnDelete();
            $table->foreignId('state_id')->nullable()->after('country_id')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('state_id');
            $table->dropConstrainedForeignId('country_id');
            $table->dropColumn(['phone', 'birthdate', 'gender']);
        });
    }
};
