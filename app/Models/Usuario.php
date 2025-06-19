<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User  as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Usuario extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $table = 'tb_usuario';
    protected $primaryKey = 'idUsuario';
    public $timestamps = false;

    protected $fillable = [
        'nome',
        'email',
        'telefone',
        'senha',
        'funcao',
        'dataCadastro',
        'eliminado',
        'acessoSistema',
        'nivel',
        'endereco',
        'genero',
        'dataNascimento',
        'srcImagem',
    ];

    protected $casts = [
        'dataCadastro' => 'datetime',
        'dataNascimento' => 'datetime',
        'eliminado' => 'boolean',
        'acessoSistema' => 'boolean',
    ];

    protected $appends = ['imagem'];

    public function getImagemAttribute()
    {
        $value = $this->srcImagem;

        if (!$this->srcImagem) {
            return url('api/usuarios/imagem/default-usuario.webp');
        }
        return url('api/usuarios/imagem/' . $value);
    }
}
