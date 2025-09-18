<?php

namespace App\Models;

use Carbon\Carbon;
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

    protected $appends = ['totalDividas', 'mensalidade'];


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

    public function frequencias()
    {
        return $this->hasMany(Frequencia::class, 'idAlunoTurma', 'idAlunoTurma');
    }

    public function usuarioTermino()
    {
        return $this->belongsTo(Usuario::class, 'idUsuarioTermino', 'idUsuario');
    }

    public function pagamentos()
    {
        return $this->hasMany(PagamentoPropina::class, 'idAlunoTurma')->latest('idAlunoTurma');
    }

    public function pagamento()
    {
        return $this->hasOne(PagamentoPropina::class, 'idAlunoTurma', 'idAlunoTurma');
    }

    public function getTotalDividasAttribute()
    {
        $inicio = Carbon::parse($this->dataCadastro);
        $fim = $this->terminado ? Carbon::parse($this->dataTermino) : now();

        // todos os meses entre inicio e fim
        $mesesPeriodo = [];
        $cursor = $inicio->copy();
        while ($cursor->lte($fim)) {
            $mesesPeriodo[] = $cursor->format('Y-m'); // ano-mês
            $cursor->addMonth();
        }

        // meses pagos
        $mesesPagos = $this->pagamentos()
            ->pluck('dataPagamento') // ou a coluna que guarda a data do pagamento
            ->map(fn($d) => Carbon::parse($d)->format('Y-m'))
            ->unique()
            ->toArray();

        // filtrar só os meses sem pagamento
        $mesesDivida = array_diff($mesesPeriodo, $mesesPagos);

        return count($mesesDivida);
    }

    public function getMensalidadeAttribute()
    {
        // garante que busca só uma vez se não tiver carregado
        $aluno = $this->relationLoaded('aluno') ? $this->aluno : $this->aluno()->first();

        if (!$aluno || !$aluno->faixaEtaria) {
            return null;
        }

        $mensalidade = \App\Models\FaixaEtariaMensalidade::where('faixaEtaria', $aluno->faixaEtaria)->first();

        return $mensalidade?->mensalidade;
    }
}
