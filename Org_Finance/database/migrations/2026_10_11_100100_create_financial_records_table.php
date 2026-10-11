<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_records', function (Blueprint $table) {
            $table->id();
            $table->string('ref', 20)->nullable()->unique();
            $table->string('title', 60);
            $table->string('type', 10);        // Income | Expense
            $table->string('category', 40);
            $table->decimal('amount', 12, 2);
            $table->date('date');
            $table->string('status', 10)->default('Pending');   // Pending | Approved
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_records');
    }
};
