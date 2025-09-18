<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AtividadeSistemaNotificacao extends Model
{
    protected $table = 'tb_atividade_sistema_notificacao';
    protected $primaryKey = 'idAtividadeSistemaNotificacao';
    public $timestamps = false;

    protected $fillable = [
        'descricao',
        'icon',
        'dataCadastro',
    ];
}
