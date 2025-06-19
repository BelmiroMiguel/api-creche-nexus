<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Aluno extends Model
{
    use HasFactory;

    protected $table = 'tb_aluno';
    protected $primaryKey = 'idAluno';
    public $timestamps = false;

    protected $fillable = [
        'nome',
        'identificacao',
        'srcImagem',
        'matricula',
        'nomeResponsavel',
        'dataNascimento',
        'observacao',
        'endereco',
        'eliminado',
        'identificacaoResponsavel',
        'telefoneResponsavel',
        'emailResponsavel',
        'grauParentesco',
        'idUsuarioRegistro',
        'genero',
    ];

    protected $casts = [
        'dataNascimento' => 'date',
        'dataCadastro' => 'datetime',
        'eliminado' => 'boolean',
    ];

    public function usuarioRegistro()
    {
        return $this->belongsTo(Usuario::class, 'idUsuarioRegistro', 'idUsuario');
    }

    public function getTurmaAttribute()
    {
        return $this->turmas->sortByDesc('pivot.idAlunoTurma')->first();
    }

    //aluno turma
    public function confirmacoes()
    {
        return $this->hasMany(AlunoTurma::class, 'idAluno')->latest('idAlunoTurma');
    }

    public function getConfirmacaoAttribute()
    {
        return $this->confirmacoes()->first();
    }


    protected $appends = ['imagem', 'confirmacao'];

    public function getImagemAttribute()
    {
        return $this->srcImagem
            ? url('api/alunos/imagem/' . $this->srcImagem)
            : url('api/usuarios/imagem/default-usuario.webp');
    }
}
