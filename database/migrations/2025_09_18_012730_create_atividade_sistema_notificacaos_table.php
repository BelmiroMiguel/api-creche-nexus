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
        Schema::create('tb_atividade_sistema_notificacao', function (Blueprint $table) {
            $table->bigIncrements('idAtividadeSistemaNotificacao');
            $table->string('descricao', 255);
            $table->string('icon', 255);
            $table->timestamp('dataCadastro')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tb_atividade_sistema_notificacao');
    }
};
