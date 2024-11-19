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
        Schema::create('empresas', function (Blueprint $table) {
            $table->id('id_empresa');
            $table->string('nombreEmpresa');
            $table->string('encargado');
            $table->string('rubroEmpresa');
            $table->string('celulares');
            $table->string('direccionEmpresa', 255);
            $table->string('correoEmpresa')->nullable();
            $table->boolean('estadoEmpresa')->default(1)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empresas');
    }
};
