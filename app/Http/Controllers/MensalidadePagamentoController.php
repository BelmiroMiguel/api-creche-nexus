<?php

namespace App\Http\Controllers;

use App\Models\AlunoTurma;
use App\Models\AtividadeSistemaNotificacao;
use App\Models\FaixaEtariaMensalidade;
use App\Models\PagamentoPropina;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class MensalidadePagamentoController extends Controller
{


    public function pagarMensalidade(Request $request)
    {
        try {
            $validator = $request->validate([
                'idAlunoTurma' => 'required|exists:tb_aluno_turma,idAlunoTurma',
                'mes' => 'required|integer|min:1|max:12',
                'ano' => 'required|integer|min:1900|max:2100',
            ], [
                'idAlunoTurma.required' => 'O campo Aluno Turma é obrigatório.',
                'idAlunoTurma.exists' => 'O Aluno Turma informado não existe.',
                'mes.required' => 'O campo Mês é obrigatório.',
                'mes.integer' => 'O Mês deve ser um número inteiro.',
                'mes.min' => 'O Mês deve ser no mínimo 1.',
                'mes.max' => 'O Mês deve ser no máximo 12.',
                'ano.required' => 'O campo Ano é obrigatório.',
                'ano.integer' => 'O Ano deve ser um número inteiro.',
                'ano.min' => 'O Ano informado é inválido.',
                'ano.max' => 'O Ano informado é inválido.',
            ]);

            $alunoTurma = AlunoTurma::with('aluno', 'pagamentos')->find($request->input('idAlunoTurma'));

            if (!$alunoTurma) {
                return response()->json([
                    'error' => 'Aluno Turma não encontrado!',
                ], 404);
            }

            $dataFim = Carbon::create($request->input('ano'), $request->input('mes'), 1)->subMonth(); // mês/ano que quer pagar
            $dataInicio = Carbon::parse($alunoTurma->dataCadastro)->startOfMonth();       // data de cadastro

            $mesesNaoPagos = [];
            $proxMesNaoPago = null;

            while ($dataFim->greaterThanOrEqualTo($dataInicio)) {
                $mes = $dataFim->month;
                $ano = $dataFim->year;

                $pagamento = $alunoTurma->pagamentos->first(function ($p) use ($mes, $ano) {
                    return $p->mes == $mes && $p->ano == $ano;
                });

                if (!$pagamento) {
                    $mesesNaoPagos[] = [
                        'mes' => $mes,
                        'ano' => $ano,
                    ];

                    // o próximo mês não pago é o mais recente retroativo
                    $proxMesNaoPago = [
                        'mes' => $mes,
                        'ano' => $ano,
                    ];
                }

                $dataFim->subMonth(); // retrocede um mês
            }

            if (!empty($mesesNaoPagos)) {
                $mesesPt = [
                    1 => 'Janeiro',
                    2 => 'Fevereiro',
                    3 => 'Março',
                    4 => 'Abril',
                    5 => 'Maio',
                    6 => 'Junho',
                    7 => 'Julho',
                    8 => 'Agosto',
                    9 => 'Setembro',
                    10 => 'Outubro',
                    11 => 'Novembro',
                    12 => 'Dezembro',
                ];

                $mesNome = $mesesPt[$proxMesNaoPago['mes']];
                $mensagem = "Pague a mensalidade de {$mesNome} de {$proxMesNaoPago['ano']}";

                return response()->json([
                    'message' => $mensagem,
                    'proxMesNaoPago' => $proxMesNaoPago, // o mês mais próximo a pagar
                    'mesesNaoPagos' => $mesesNaoPagos,
                ], 401);
            }

            $usuario = $request->user();
            if (!$usuario || !isset($usuario->idUsuario)) {
                return response()->json(['message' => 'Usuário autenticado não encontrado.'], 401);
            }

            $mesesPT = [
                1 => 'Janeiro',
                2 => 'Fevereiro',
                3 => 'Março',
                4 => 'Abril',
                5 => 'Maio',
                6 => 'Junho',
                7 => 'Julho',
                8 => 'Agosto',
                9 => 'Setembro',
                10 => 'Outubro',
                11 => 'Novembro',
                12 => 'Dezembro'
            ];

            AtividadeSistemaNotificacao::create([
                'descricao' => "
                    <span class='descricao-atividade'>
                      Pagamento recebido de <strong>{$alunoTurma->aluno->nome}</strong> para ({$mesesPT[$request->input('mes')]} de {$request->input('ano')})
                    </span>
                ",
                'icon' => ' fa-solid fa-file-invoice-dollar ; color: #dac50ed7'
            ]);

            $validator['idUsuarioRegistro'] = $usuario->idUsuario;
            PagamentoPropina::create([
                ...$validator,
                'mensalidade' => $alunoTurma['mensalidade']
            ]);

            return response()->json([
                'message' =>   'Mensalidade paga com suceso',
                'alunoTurma' => $alunoTurma,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'Erro interno!',
                'message' => $th->getMessage(),
            ], 500);
        }
    }


    public function getAlunosMensalidades(Request $request)
    {
        try {
            $items = $request->input('items', 15);
            $page = $request->input('page', null);

            $ano = $request->input('ano', date('Y'));
            $mes = $request->input('mes', date('n'));
            $statusPagamento = $request->input('statusPagamento'); // 1 = pagou, 0 = não pagou

            $filtro = Carbon::create($ano, $mes, 1);

            $query = AlunoTurma::with(['aluno', 'turma', 'pagamentos',  'pagamento' => function ($q) use ($ano, $mes) {
                $q->where('ano', $ano)->where('mes', $mes);
            }])
                ->where('terminado', false);

            $value = $request->input('value');
            // Aplica filtro de busca genérica
            if ($value) {
                $query->whereHas('aluno', function ($q) use ($value) {
                    $q->where('nome', 'like', "%{$value}%")
                        ->orWhere('identificacao', 'like', "%{$value}%")
                        ->orWhere('nomeResponsavel', 'like', "%{$value}%")
                        ->orWhere('identificacaoResponsavel', 'like', "%{$value}%")
                        ->orWhere('matricula', 'like', "%{$value}%")
                        ->orWhere('telefoneResponsavel', 'like', "%{$value}%")
                        ->orWhere('dataNascimento', 'like', "%{$value}%");
                });
            }

            // aplica filtro por statusPagamento de pagamento
            if ($statusPagamento !== null) {
                if ($statusPagamento == 1) {
                    // só quem pagou no ano/mês
                    $query->whereHas('pagamento', function ($q) use ($ano, $mes) {
                        $q->where('ano', $ano)->where('mes', $mes);
                    });
                } elseif ($statusPagamento == 0) {
                    // só quem não pagou no ano/mês
                    $query->whereDoesntHave('pagamento', function ($q) use ($ano, $mes) {
                        $q->where('ano', $ano)->where('mes', $mes);
                    });
                }
            }


            if ($request->filled('page') && $request->input('page') != null) {
                // Ordenação e paginação
                $confirmacoesPaginator = $query->paginate($items, ['*'], 'page', $page);
                $confirmacoes = $confirmacoesPaginator->items();

                $confirmacoes = collect($confirmacoesPaginator->items());
                $confirmacoes = $confirmacoes->map(function ($alunoTurma) use ($ano, $mes) {
                    $alunoTurma = $alunoTurma->toArray();
                    $dataCadastro = Carbon::parse($alunoTurma['dataCadastro']);

                    // normaliza para números
                    $ano = (int) $ano;
                    $mes = (int) $mes;
                    $anoCadastro = (int) $dataCadastro->year;
                    $mesCadastro = (int) $dataCadastro->month;

                    // se dataCadastro for superior ao filtro, zera mensalidade
                    if ($anoCadastro > $ano || ($anoCadastro === $ano && $mesCadastro > $mes)) {
                        $alunoTurma['mensalidade'] = null;
                    }

                    return $alunoTurma;
                });

                return response()->json([
                    'message' => 'Mensalidades carregadas com sucesso.',
                    'body' => $confirmacoes,
                    'paginacao' => [
                        'totalPages' => $confirmacoesPaginator->lastPage(),
                        'totalItems' => $confirmacoesPaginator->total(),
                        'items' => $confirmacoesPaginator->perPage(),
                        'page' => $confirmacoesPaginator->currentPage(),
                    ]
                ], 200);
            } else {
                $confirmacoes = $query->get();

                $confirmacoes = $confirmacoes->map(function ($alunoTurma) use ($ano, $mes) {
                    $dataCadastro = Carbon::parse($alunoTurma->dataCadastro);
                    $alunoTurma = $alunoTurma->toArray();

                    if ($dataCadastro->year < $ano || ($dataCadastro->year == $ano && $dataCadastro->month < $mes)) {
                        $alunoTurma['mensalidade'] = null;
                    }

                    return $alunoTurma;
                });

                return response()->json([
                    'message' => 'Mensalidades carregadas com sucesso.',
                    'body' => $confirmacoes
                ], 200);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'Erro interno!',
                'message' => $th->getMessage(),
            ], 500);
        }
    }


    public function countAlunosMensalidades(Request $request)
    {
        try {
            $statusPagamento = $request->input('statusPagamento'); // 1 = pagou, 0 = não pagou
            $hoje = Carbon::today()->startOfMonth();

            $query = AlunoTurma::with('pagamentos')
                ->where('terminado', false);

            $alunosTurma = $query->get();

            $filtrados = $alunosTurma->filter(function ($alunoTurma) use ($statusPagamento, $hoje) {
                $dataCadastro = Carbon::parse($alunoTurma->dataCadastro)->startOfMonth();
                $dataIter = $dataCadastro->copy();

                $mesesNaoPagos = [];

                while ($dataIter->lessThanOrEqualTo($hoje)) {
                    $mes = $dataIter->month;
                    $ano = $dataIter->year;

                    $pagou = $alunoTurma->pagamentos->contains(function ($p) use ($mes, $ano) {
                        return (int)$p->mes === $mes && (int)$p->ano === $ano;
                    });

                    if (!$pagou) {
                        $mesesNaoPagos[] = "{$mes}/{$ano}";
                    }

                    $dataIter->addMonth();
                }

                $estaEmDia = empty($mesesNaoPagos);

                if ($statusPagamento == null) return true; // sem filtro
                if ($statusPagamento == 1) return $estaEmDia; // só quem está em dia
                if ($statusPagamento == 0) return !$estaEmDia; // só quem está devendo

                return true;
            });

            // contador e soma de valores pagos (até hoje)
            $qtd = $filtrados->count();
            $valor = 0;
            foreach ($filtrados as $alunoTurma) {
                $valor += (float) $alunoTurma->mensalidade;
            }


            return response()->json([
                'message' => 'Resumo de mensalidades carregado com sucesso.',
                'body' => [
                    'qtd' => $qtd,
                    'valor' => $valor,
                    'filtrados' => $filtrados
                ]
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'Erro interno!',
                'message' => $th->getMessage(),
            ], 500);
        }
    }


    public function getMensalidaeFaixaEtaria(Request $request)
    {
        try {
            $items = $request->input('items', 15);
            $page = $request->input('page', null);

            $query =  FaixaEtariaMensalidade::query();

            if ($request->filled('eliminado')) {
                $query->where('eliminado', $request->input('eliminado'));
            }
            $query->orderBy('idFaixaEtariaMensalidade');

            if ($request->filled('page') && $request->input('page') != null) {
                // Ordenação e paginação
                $mensalidadesPaginator = $query->paginate($items, ['*'], 'page', $page);

                return response()->json([
                    'message' => 'Mensalidades carregadas com sucesso.',
                    'body' => $mensalidadesPaginator->items(),
                    'paginacao' => [
                        'totalPages' => $mensalidadesPaginator->lastPage(),
                        'totalItems' => $mensalidadesPaginator->total(),
                        'items' => $mensalidadesPaginator->perPage(),
                        'page' => $mensalidadesPaginator->currentPage(),
                    ]
                ], 200);
            } else {
                $mensalidades = $query->get();
                return response()->json([
                    'message' => 'Mensalidades carregadas com sucesso.',
                    'body' => $mensalidades
                ], 200);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'Erro interno!',
                'message' => $th->getMessage(),
            ], 500);
        }
    }


    public function atualizarMensalidaeFaixaEtaria(Request $request)
    {
        try {
            $validator = $request->validate([
                'idFaixaEtariaMensalidade' => 'required|exists:tb_faixa_etaria_mensalidade,idFaixaEtariaMensalidade',
                'mensalidade' => 'required|numeric|min:0.01'
            ], [
                'idFaixaEtariaMensalidade.required' => 'O campo idFaixaEtariaMensalidade é obrigatório.',
                'idFaixaEtariaMensalidade.exists' => 'O idFaixaEtariaMensalidade fornecido não existe.',
                'mensalidade.required' => 'O campo mensalidade é obrigatório.',
                'mensalidade.numeric' => 'O campo mensalidade deve ser um número.',
                'mensalidade.min' => 'O valor do campo mensalidade deve ser no mínimo 0.01.',
            ]);
            $faixaEtariaMensalidade = FaixaEtariaMensalidade::where('idFaixaEtariaMensalidade', $request->input('idFaixaEtariaMensalidade'))->first();

            if (!$faixaEtariaMensalidade) {
                return response()->json([
                    'error' => 'Registro não encontrado!',
                    'message' => 'O idFaixaEtariaMensalidade fornecido não corresponde a nenhum registro.',
                ], 404);
            }

            $faixaEtariaMensalidade->update($validator);
            return response()->json([
                'message' => 'Mensalidade atualizada com sucesso.',
                'body' => $faixaEtariaMensalidade,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'Erro interno!',
                'message' => $th->getMessage(),
            ], 500);
        }
    }
}
