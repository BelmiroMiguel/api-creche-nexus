<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Turma extends Model
{
    protected $table = 'tb_turma';
    protected $primaryKey = 'idTurma';
    public $timestamps = false;

    protected $fillable = [
        'nome',
        'idEducador',
        'faixaEtariaMin',
        'faixaEtariaMax',
        'capacidade',
        'dataInicio',
        'dataTermino',
        'terminado',
        'eliminada',
        'dataCadastro',
        'idUsuarioRegistro',
        'cor', // cor da turma hexadecimal
    ];

    protected $appends = ['descFaixaEtaria'];

    protected $casts = [
        'dataInicio' => 'date',
        'dataTermino' => 'date',
        'dataCadastro' => 'datetime',
        'terminado' => 'boolean',
        'eliminada' => 'boolean',
    ];

    public function educador()
    {
        return $this->belongsTo(Usuario::class, 'idEducador', 'idUsuario');
    }

    public function usuarioRegistro()
    {
        return $this->belongsTo(Usuario::class, 'idUsuarioRegistro', 'idUsuario');
    }
    public function alunos()
    {
        return $this->belongsToMany(Aluno::class, 'tb_aluno_turma', 'idTurma', 'idAluno')
            ->wherePivot('terminado', false)
            ->where('tb_aluno.eliminado', false)
            ->withPivot('idTurma', 'idAluno');
    }


    public function getDescFaixaEtariaAttribute()
    {
        return "{$this->faixaEtariaMin} a {$this->faixaEtariaMax} anos";
    }
}
