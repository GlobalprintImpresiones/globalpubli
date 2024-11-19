<?php

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
        Schema::create('seguimientos', function (Blueprint $table) {
            $table->id('id_seguimiento');
            $table->string('condiciones')->nullable();
            $table->string('historia');
            $table->timestamps();
            $table->unsignedBigInteger('id_empresa');
            $table->unsignedBigInteger('id_pedido')->nullable();

            $table->foreign('id_empresa')->references('id_empresa')->on('empresas');
            $table->foreign('id_pedido')->references('id_pedido')->on('pedidos')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seguimientos');
    }
};
