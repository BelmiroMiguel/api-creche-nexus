<?php

namespace App\Http\Controllers;

use App\Models\AlunoTurma;
use App\Models\Turma;
use Illuminate\Http\Request;

class ConfirmacaoController extends Controller
{
    // Listar confirmações de um aluno
    public function getConfirmacoesPorTurma(Request $request)
    {
        try {
            $request->validate([
                'idTurma' => 'required|exists:tb_turma,idTurma',
            ], [
                'idTurma.required' => 'O campo Aluno é obrigatório.',
                'idTurma.exists' => 'A turma informada não existe.'
            ]);
            $dataFiltro = $request->input('dataFiltro') ? date('Y-m-d', strtotime($request->input('dataFiltro'))) : null;
            $statusFrequencia = $request->input('statusFrequencia'); // 'presente', 'ausente' ou null

            $items = $request->input('items', 15);
            $page = $request->input('page',);

            $query = AlunoTurma::with(['turma', 'aluno', 'frequencias', 'usuarioRegistro', 'usuarioTermino'])
                ->where('idTurma', $request->input('idTurma'))
                ->whereHas('aluno', function ($query) {
                    $query->where('eliminado', false);
                })
                ->where('terminado', false)
                ->when($statusFrequencia === 'presente' && $dataFiltro, function ($query) use ($dataFiltro) {
                    $query->whereHas('frequencias', function ($q) use ($dataFiltro) {
                        $q->whereDate('dataFrequencia', $dataFiltro);
                    });
                })
                ->when($statusFrequencia === 'ausente' && $dataFiltro, function ($query) use ($dataFiltro) {
                    $query->whereDoesntHave('frequencias', function ($q) use ($dataFiltro) {
                        $q->whereDate('dataFrequencia', $dataFiltro);
                    });
                })
                ->orderByDesc('idAlunoTurma');


            $paginacao = [];

            if ($page && $page > 0) {
                $confirmacoes = $query->paginate($items, ['*'], 'page', $page);
                $paginacao = [
                    'totalPages' => $confirmacoes->lastPage(),
                    'totalItems' => $confirmacoes->total(),
                    'items' => $confirmacoes->perPage(),
                    'page' => $confirmacoes->currentPage(),
                ];
                $confirmacoes = $confirmacoes->items();
            } else {
                $confirmacoes = $query->get();
                $paginacao = [
                    'totalPages' => 1,
                    'totalItems' => count($confirmacoes),
                    'items' => 0,
                    'page' => 1,
                ];
            }


            return response()->json([
                'message' => 'Confirmações encontradas com sucesso.',
                'body' => $confirmacoes,
                'paginacao' => $paginacao ?? null
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'Erro ao buscar confirmações.',
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    // Confirmar vinculação de aluno a turma
    public function confirmar(Request $request)
    {
        try {
            $request->validate(
                [
                    'idAluno' => 'required|exists:tb_aluno,idAluno',
                    'idTurma' => 'required|exists:tb_turma,idTurma'
                ],
                [
                    'idAluno.required' => 'O campo Aluno é obrigatório.',
                    'idAluno.exists' => 'O aluno informado não existe.',
                    'idTurma.required' => 'O campo Turma é obrigatório.',
                    'idTurma.exists' => 'A turma informada não existe.'
                ]
            );


            $usuario = $request->user();
            if (!$usuario || !isset($usuario->idUsuario)) {
                return response()->json(['message' => 'Usuário autenticado não encontrado.'], 401);
            }

            // Verifica se o aluno já possui uma confirmação não terminada
            $existeConfirmacao = AlunoTurma::where('idAluno', $request->idAluno)
                ->where('terminado', false)
                ->exists();

            if ($existeConfirmacao) {
                return response()->json([
                    'message' => 'O aluno já possui uma confirmação, termine a confirmação anterior.'
                ], 409);
            }

            $capacidade = Turma::where('idTurma', $request->idTurma)->value('capacidade');
            if (!$capacidade) {
                return response()->json([
                    'message' => 'A turma informada não possui capacidade definida.'
                ], 422);
            }
            // Verifica se a turma já atingiu o limite de alunos
            $qtdAlunosNaTurma = AlunoTurma::where('idTurma', $request->idTurma)
                ->where('terminado', false)
                ->count();

            if ($qtdAlunosNaTurma >= $capacidade) {
                return response()->json([
                    'message' => 'A turma informada já atingiu o limite máximo de alunos.'
                ], 422);
            }

            // Cria a confirmação de vinculação
            $alunoTurma = AlunoTurma::create([
                'idAluno' => $request->idAluno,
                'idTurma' => $request->idTurma,
                'idUsuarioRegistro' => $usuario->idUsuario,
                'dataCadastro' => now(),
                'terminado' => false
            ]);

            return response()->json([
                'message' => 'Confirmação vinculada com sucesso.',
                'body' => $alunoTurma,
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'Erro ao confirmar vinculação.',
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    // Encerrar uma vinculação
    public function encerrar(Request $request)
    {
        try {
            $request->validate([
                'idAlunoTurma' => 'required|integer|exists:tb_aluno_turma,idAlunoTurma'
            ], [
                'idAlunoTurma.required' => 'A turma de confirmação é obrigatória.',
                'idAlunoTurma.integer' => 'A turma de confirmação é inválida.',
                'idAlunoTurma.exists' => 'A turma de confirmação informada não existe.'
            ]);

            $alunoTurma = AlunoTurma::where('idAlunoTurma', $request->input('idAlunoTurma'))->firstOrFail();
            $usuario = $request->user();

            $alunoTurma->update([
                'terminado' => true,
                'dataTermino' => now(),
                'idUsuarioTermino' => $usuario->idUsuario ?? null,
            ]);

            return response()->json([
                'message' => 'Confirmação encerrada com sucesso.',
                'body' => $alunoTurma,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'Erro ao encerrar confirmação.',
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    // Trocar aluno de turma
    public function trocarTurma(Request $request)
    {
        try {
            $request->validate([
                'idAluno' => 'required|exists:tb_aluno,idAluno',
                'idTurma' => 'required|exists:tb_turma,idTurma'
            ], [
                'idAluno.required' => 'O campo Aluno é obrigatório.',
                'idAluno.exists' => 'O aluno informado não existe.',
                'idTurma.required' => 'O campo Turma Nova é obrigatório.',
                'idTurma.exists' => 'A turma nova informada não existe.'
            ]);

            $usuario = $request->user();
            if (!$usuario || !isset($usuario->idUsuario)) {
                return response()->json(['message' => 'Usuário autenticado não encontrado.'], 401);
            }

            $capacidade = Turma::where('idTurma', $request->idTurma)->value('capacidade');
            if (!$capacidade) {
                return response()->json([
                    'message' => 'A turma informada não possui capacidade definida.'
                ], 422);
            }
            // Verifica se a turma já atingiu o limite de alunos
            $qtdAlunosNaTurma = AlunoTurma::where('idTurma', $request->idTurma)
                ->where('terminado', false)
                ->count();

            if ($qtdAlunosNaTurma >= $capacidade) {
                return response()->json([
                    'message' => 'A turma informada já atingiu o limite máximo de alunos.'
                ], 422);
            }

            // Encerra a confirmação atual, se existir e não estiver terminada
            $alunoTurmaAtual = AlunoTurma::where('idAluno', $request->idAluno)
                ->where('terminado', false)
                ->first();

            if ($alunoTurmaAtual) {
                $alunoTurmaAtual->update([
                    'terminado' => true,
                    'dataTermino' => now(),
                    'idUsuarioTermino' => $usuario->idUsuario,
                ]);
            }

            // Cria nova confirmação para a nova turma
            $novaConfirmacao = AlunoTurma::create([
                'idAluno' => $request->idAluno,
                'idTurma' => $request->idTurma,
                'idUsuarioRegistro' => $usuario->idUsuario,
                'dataCadastro' => now(),
                'terminado' => false
            ]);

            return response()->json([
                'message' => 'Troca de turma realizada com sucesso.',
                'body' => $novaConfirmacao,
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'Erro ao trocar turma.',
                'message' => $th->getMessage(),
            ], 500);
        }
    }
}
