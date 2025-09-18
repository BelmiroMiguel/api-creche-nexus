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
        Schema::create('tb_faixa_etaria_mensalidade', function (Blueprint $table) {
            $table->bigIncrements('idFaixaEtariaMensalidade');
            $table->string('faixaEtaria', 5);
            $table->decimal('mensalidade');
            $table->boolean('eliminado')->default(false);
            $table->timestamp('dataCadastro')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tb_faixa_etaria_mensalidade');
    }
};
