<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    
    /**
     * @return void
     */
    public function up(): void {
        if (Schema::hasTable('customer')) {
            return;
        }
        
        Schema::create('customer', function (Blueprint $table): void {
            $table->id();
            $table->string('email')->unique();
            $table->string('name');
            $table->string('password')->nullable();
        });
    }
    
    /**
     * @return void
     */
    public function down(): void {
        Schema::dropIfExists('customer');
    }
};
