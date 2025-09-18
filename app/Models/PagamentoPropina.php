<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PagamentoPropina extends Model
{
    use HasFactory;

    protected $table = 'tb_pagamento_propinas';
    protected $primaryKey = 'idPagamentoPropina';
    public $timestamps = false;

    protected $fillable = [
        'idUsuarioRegistro',
        'idAlunoTurma',
        'mes',
        'ano',
        'mensalidade',
        'eliminado',
        'dataPagamento',
    ];

    protected $casts = [
        'ano' => 'integer',
        'mes' => 'integer',
        'mensalidade' => 'decimal:2',
        'eliminado' => 'boolean',
        'dataPagamento' => 'datetime',
    ];

    // 🔹 Relacionamentos
    public function usuarioRegistro()
    {
        return $this->belongsTo(Usuario::class, 'idUsuarioRegistro', 'idUsuario');
    }

    public function alunoTurma()
    {
        return $this->belongsTo(AlunoTurma::class, 'idAlunoTurma', 'idAlunoTurma');
    }

    // 🔹 Acessores
    protected $appends = ['mesAno'];

    public function getMesAnoAttribute()
    {
        return sprintf('%02d/%d', $this->mes, $this->ano);
    }
}
