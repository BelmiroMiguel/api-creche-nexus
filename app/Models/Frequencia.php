<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Frequencia extends Model
{
    use HasFactory;

    protected $table = 'tb_frequencia_aluno_turma';
    protected $primaryKey = 'idFrequenciaAlunoTurma';
    public $timestamps = false;

    protected $fillable = [
        'idAlunoTurma',
        'idUsuarioRegistro',
        'horaEntrada',
        'horaSaida',
        'observacaoEntrada',
        'observacaoSaida',
        'nomeResponsavelEntrega',
        'nomeResponsavelBusca',
        'eliminado',
        'dataFrequencia',
        'dataCadastro',
    ];

    protected $casts = [
        'horaEntrada' => 'datetime:H:i',
        'horaSaida' => 'datetime:H:i',
        'dataFrequencia' => 'date',
        'dataCadastro' => 'datetime',
        'eliminado' => 'boolean',
    ];

    public function confirmacao()
    {
        return $this->belongsTo(AlunoTurma::class, 'idAlunoTurma', 'idAlunoTurma');
    }

    public function usuarioRegistro()
    {
        return $this->belongsTo(Usuario::class, 'idUsuarioRegistro', 'idUsuario');
    }
}
