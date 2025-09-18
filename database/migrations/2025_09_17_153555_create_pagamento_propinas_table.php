<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('tb_pagamento_propinas', function (Blueprint $table) {
            $table->bigIncrements('idPagamentoPropina');
            $table->unsignedBigInteger('idUsuarioRegistro');
            $table->unsignedBigInteger('idAlunoTurma');
            $table->tinyInteger('mes')->unsigned(); // 1 a 12
            $table->year('ano');
            $table->decimal('mensalidade');
            $table->boolean('eliminado')->default(false);
            $table->timestamp('dataPagamento')->useCurrent();

            $table->foreign('idUsuarioRegistro')->references('idUsuario')->on('tb_usuario');
            $table->foreign('idAlunoTurma')->references('idAlunoTurma')->on('tb_aluno_turma');
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('tb_pagamento_propinas');
    }
};
