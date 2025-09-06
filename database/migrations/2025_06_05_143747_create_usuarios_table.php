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
        Schema::create('tb_usuario', function (Blueprint $table) {
            $table->bigIncrements('idUsuario');
            $table->string('nome', 150)->nullable();
            $table->string('genero', 150)->nullable();
            $table->string('endereco', 255)->nullable();
            $table->date('dataNascimento')->nullable();

            $table->string('telefone', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('senha', 75)->nullable();
            $table->string('funcao', 75)->nullable();

            $table->enum('nivel', ['admin', 'gestao', 'educador', 'auxiliar', 'usuario'])->default('usuario');

            $table->timestamp('dataCadastro')->useCurrent();
            $table->boolean('acessoSistema')->default(false);
            $table->boolean('eliminado')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};
