<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    
    /**
     * @return void
     */
    public function up(): void {
        if (!Schema::hasTable('transaction_record')) {
            return;
        }
        
        if (Schema::hasColumn('transaction_record', 'customer_id')) {
            return;
        }
        
        Schema::table('transaction_record', function (Blueprint $table): void {
            $table->foreignId('customer_id')
                ->nullable()
                ->after('transaction_uid')
                ->constrained('customer');
        });
    }
    
    /**
     * @return void
     */
    public function down(): void {
        if (!Schema::hasTable('transaction_record') || !Schema::hasColumn('transaction_record', 'customer_id')) {
            return;
        }
        
        Schema::table('transaction_record', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('customer_id');
        });
    }
};
