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
        Schema::create('tb_aluno', function (Blueprint $table) {
            $table->bigIncrements('idAluno');
            $table->string('nome', 150)->nullable();
            $table->string('genero', 150)->nullable();
            $table->string('matricula', 150)->nullable();
            $table->string('identificacao', 100)->nullable();
            $table->string('srcImagem', 255)->nullable();
            $table->string('grauParentesco', 150)->nullable();
            $table->string('grauParentesco2', 150)->nullable();
            $table->string('grauParentesco3', 150)->nullable();
            $table->string('grauParentesco4', 150)->nullable();
            $table->string('nomeResponsavel', 150)->nullable();
            $table->string('nomeResponsavel2', 150)->nullable();
            $table->string('nomeResponsavel3', 150)->nullable();
            $table->string('nomeResponsavel4', 150)->nullable();
            $table->date('dataNascimento')->nullable();
            $table->string('observacao', 255)->nullable();
            $table->string('endereco', 255)->nullable();
            $table->timestamp('dataCadastro')->useCurrent();
            $table->boolean('eliminado')->default(false);
            $table->string('identificacaoResponsavel', 100)->nullable();
            $table->string('telefoneResponsavel', 20)->nullable();
            $table->string('telefoneResponsavel2', 20)->nullable();
            $table->string('telefoneResponsavel3', 20)->nullable();
            $table->string('telefoneResponsavel4', 20)->nullable();
            $table->unsignedBigInteger('idUsuarioRegistro')->nullable();

            $table->foreign('idUsuarioRegistro')->references('idUsuario')->on('tb_usuario');
        });

        Schema::create('tb_turma', function (Blueprint $table) {
            $table->bigIncrements('idTurma');
            $table->string('nome', 150)->nullable();
            $table->string('cor', 150)->nullable();
            $table->unsignedBigInteger('idEducador')->nullable();
            $table->string('faixaEtariaMin')->nullable();
            $table->string('faixaEtariaMax')->nullable();
            $table->integer('capacidade')->nullable();
            $table->date('dataInicio')->nullable();
            $table->date('dataTermino')->nullable();
            $table->boolean('finalizada')->default(false);
            $table->boolean('eliminada')->default(false);
            $table->timestamp('dataCadastro')->useCurrent();
            $table->unsignedBigInteger('idUsuarioRegistro')->nullable();


            $table->foreign('idEducador')->references('idUsuario')->on('tb_usuario');
            $table->foreign('idUsuarioRegistro')->references('idUsuario')->on('tb_usuario');
        });

        Schema::create('tb_aluno_turma', function (Blueprint $table) {
            $table->bigIncrements('idAlunoTurma');
            $table->unsignedBigInteger('idAluno')->nullable();
            $table->unsignedBigInteger('idTurma')->nullable();
            $table->timestamp('dataCadastro')->useCurrent();
            $table->timestamp('dataTermino')->nullable();
            $table->unsignedBigInteger('idUsuarioRegistro')->nullable();
            $table->unsignedBigInteger('idUsuarioTermino')->nullable();
            $table->boolean('terminado')->default(false);

            $table->foreign('idAluno')->references('idAluno')->on('tb_aluno');
            $table->foreign('idTurma')->references('idTurma')->on('tb_turma');
            $table->foreign('idUsuarioRegistro')->references('idUsuario')->on('tb_usuario');
            $table->foreign('idUsuarioTermino')->references('idUsuario')->on('tb_usuario');
        });

        Schema::create('tb_frequencia_aluno_turma', function (Blueprint $table) {
            $table->bigIncrements('idFrequenciaAlunoTurma');
            $table->unsignedBigInteger('idAlunoTurma')->nullable();
            $table->timestamp('dataHoraEntrada')->nullable();
            $table->timestamp('dataHoraSaida')->nullable();
            $table->string('observacao', 255)->nullable();
            $table->enum('statusPresenca', ['presente', 'ausente'])->default('ausente');
            $table->string('responsavelEntrega', 150)->nullable();
            $table->string('responsavelBusca', 150)->nullable();
            $table->boolean('eliminado')->default(false);
            $table->unsignedBigInteger('idUsuarioRegistro')->nullable();

            $table->foreign('idAlunoTurma')->references('idAlunoTurma')->on('tb_aluno_turma');
            $table->foreign('idUsuarioRegistro')->references('idUsuario')->on('tb_usuario');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tb_frequencia_aluno_turma');
        Schema::dropIfExists('tb_aluno_turma');
        Schema::dropIfExists('tb_turma');
        Schema::dropIfExists('tb_aluno');
    }
};
