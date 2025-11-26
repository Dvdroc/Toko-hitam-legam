<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up(): void
{
    Schema::create('production_slots', function (Blueprint $table) {
        $table->id();
        $table->date('date')->unique(); 
        $table->integer('quota')->default(200); 
        $table->integer('used_quota')->default(0); 
        $table->boolean('is_closed')->default(false); 
        $table->timestamps();
    });
}
};
