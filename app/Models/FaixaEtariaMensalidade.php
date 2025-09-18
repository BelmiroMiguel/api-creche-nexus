<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FaixaEtariaMensalidade extends Model
{
    use HasFactory;

    protected $table = 'tb_faixa_etaria_mensalidade';
    protected $primaryKey = 'idFaixaEtariaMensalidade';
    public $timestamps = false;

    protected $fillable = [
        'faixaEtaria',
        'mensalidade',
        'dataCadastro',
    ];


    protected $appends = ['descFaixaEtaria'];

    public function getDescFaixaEtariaAttribute()
    {
        if (str_contains($this->attributes['faixaEtaria'], 'm')) {
            $num = (int) str_replace('m', '', $this->attributes['faixaEtaria']);
            return $num === 1
                ? "$num Mês"
                : "$num Meses";
        } else {
            $num = (int) str_replace('a', '', $this->attributes['faixaEtaria']);
            return $num === 1
                ? "$num Ano"
                : "$num Anos";
        }
    }
}
