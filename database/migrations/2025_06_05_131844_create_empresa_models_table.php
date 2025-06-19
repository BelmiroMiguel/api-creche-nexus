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
        Schema::create('tb_empresa', function (Blueprint $table) {
            $table->bigIncrements('idEmpresa');
            $table->string('nome');
            $table->string('nif');
            $table->string('email');
            $table->string('telefone');
            $table->string('endereco');
            $table->string('srcImagem')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empresa_models');
    }
};
