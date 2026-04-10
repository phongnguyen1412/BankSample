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
        Schema::create('import_error', function (Blueprint $table): void {
            $table->id();
            $table->string('queue_id');
            $table->unsignedBigInteger('row_number');
            $table->dateTime('row_date')->nullable();
            $table->text('row_content')->nullable();
            $table->text('error_message');
            $table->dateTime('created_at')->useCurrent();

            $table->index('queue_id');
            $table->foreign('queue_id')
                ->references('queue_id')
                ->on('queue_status')
                ->cascadeOnDelete();
        });
    }
    
    /**
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('import_error');
    }
};
