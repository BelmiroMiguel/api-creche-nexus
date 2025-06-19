<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Empresa extends Model
{
    protected $table = 'tb_empresa';
    protected $primaryKey = 'idEmpresa';
    public $timestamps = false;

    protected $fillable = [
        'nome',
        'nif',
        'telefone',
        'endereco',
        'srcImagem',
        'email',
    ];
}
