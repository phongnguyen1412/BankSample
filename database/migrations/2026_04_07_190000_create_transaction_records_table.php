<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    /**
     * @return void
     */
    public function up(): void
    {
        Schema::create('transaction_record', function (Blueprint $table): void {
            $table->id();
            $table->string('transaction_uid', 64)->unique();
            $table->foreignId('customer_id')->constrained('customer');
            $table->dateTime('date');
            $table->text('content');
            $table->float('amount');
            $table->integer('type');
            $table->dateTime('created_at')->useCurrent();
        });
    }
    
    /**
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_record');
    }
};
