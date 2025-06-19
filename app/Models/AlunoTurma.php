<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AlunoTurma extends Model
{
    use HasFactory;

    protected $table = 'tb_aluno_turma';
    protected $primaryKey = 'idAlunoTurma';
    public $timestamps = false;

    protected $fillable = [
        'idAluno',
        'idTurma',
        'idUsuarioRegistro',
        'dataCadastro',
        'dataTermino',
        'idUsuarioTermino',
        'terminado',
    ];

    protected $casts = [
        'dataCadastro' => 'datetime',
        'dataTermino' => 'datetime',
    ];

    public function aluno()
    {
        return $this->belongsTo(Aluno::class, 'idAluno', 'idAluno');
    }

    public function turma()
    {
        return $this->belongsTo(Turma::class, 'idTurma', 'idTurma');
    }

    public function usuarioRegistro()
    {
        return $this->belongsTo(Usuario::class, 'idUsuarioRegistro', 'idUsuario');
    }


    public function usuarioTermino()
    {
        return $this->belongsTo(Usuario::class, 'idUsuarioTermino', 'idUsuario');
    }
}
