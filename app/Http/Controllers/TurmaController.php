<?php

namespace App\Http\Controllers;

use App\Models\AlunoTurma;
use App\Models\Turma;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TurmaController extends Controller
{
    // Gera uma cor hexadecimal aleatória
    private function gerarCorHex()
    {
        $coresFundoLegiveis = [
            '#1abc9c', // verde água
            '#2ecc71', // verde esmeralda
            '#3498db', // azul claro
            '#9b59b6', // roxo
            '#34495e', // azul petróleo
            '#f39c12', // laranja escuro
            '#e67e22', // laranja
            '#e74c3c', // vermelho
            '#c0392b', // vermelho escuro
            '#d35400', // laranja queimado
            '#8e44ad', // roxo escuro
            '#2980b9', // azul
            '#16a085', // verde escuro
            '#27ae60', // verde vibrante
            '#2c3e50', // cinza escuro
        ];

        return $coresFundoLegiveis[array_rand($coresFundoLegiveis)];
    }


    // Cadastrar nova turma
    public function cadastrarTurma(Request $request)
    {
        try {
            $validator = $request->validate([
                'nome' => 'required|string|max:255',
                'idEducador' => 'required|exists:tb_usuario,idUsuario',
                'faixaEtariaMin' => ['required', 'string', 'regex:/^\d{1,2}(m|a)$/'],
                'faixaEtariaMax' => ['nullable', 'string', 'regex:/^\d{1,2}(m|a)$/'],
                'capacidade' => 'required|integer|min:1|max:100',
                'dataInicio' => 'required|date|after:today',
                'dataTermino' => 'nullable|date|after:dataInicio',
                'cor' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            ], [
                'nome.required' => 'Campo nome é obrigatório',
                'idEducador.required' => 'Educador principal é obrigatório',
                'idEducador.exists' => 'Educador não encontrado',
                'faixaEtariaMin.regex' => 'Faixa etária mínima deve ser um valor válido (ex: 2m, 1a)',
                'faixaEtariaMax.regex' => 'Faixa etária máxima deve ser um valor válido (ex: 2m, 1a)',
                'faixaEtariaMin.required' => 'Faixa etária mínima é obrigatória',
                'capacidade.integer' => 'Capacidade deve ser um número inteiro',
                'capacidade.min' => 'Capacidade deve ser pelo menos 1',
                'capacidade.max' => 'Capacidade deve ser no máximo 100',
                'capacidade.required' => 'Capacidade é obrigatória',
                'dataInicio.required' => 'Data de início é obrigatória',
                'cor.regex' => 'Cor deve ser um valor hexadecimal válido (ex: #FF5733)',
                'dataInicio.date' => 'Data de início deve ser uma data válida',
                'dataInicio.after' => 'Data de início deve ser posterior a hoje',
                'dataTermino.after' => 'Data de término deve ser posterior à data de início',
                'faixaEtariaMax.gte' => 'Faixa etária máxima deve ser maior ou igual à faixa etária mínima',
            ]);

            $usuario = $request->user();
            if (!$usuario || !isset($usuario->idUsuario)) {
                return response()->json(['message' => 'Usuário autenticado não encontrado.'], 401);
            }

            $validator['dataCadastro'] = now();
            $validator['finalizada'] = false;
            $validator['eliminada'] = false;
            $validator['idUsuarioRegistro'] = $usuario->idUsuario;
            $validator['cor'] = $validator['cor'] ?? $this->gerarCorHex();

            $turma = Turma::create($validator);

            return response()->json([
                'message' => 'Turma cadastrada com sucesso!',
                'body' => $turma,
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'Erro ao cadastrar turma.',
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    // Listar turmas (com paginação e busca)
    public function getTurmas(Request $request)
    {
        try {
            $items = $request->input('items', 15);
            $page = $request->input('page', 1);
            $searchableFields = ['nome'];
            $value = $request->input('value');

            $query = Turma::with(['educador', 'usuarioRegistro', 'alunos']);

            if ($value) {
                $query->where(function ($q) use ($searchableFields, $value) {
                    foreach ($searchableFields as $field) {
                        $q->orWhere($field, 'like', "%{$value}%");
                    }
                    // Busca pelo nome do educador relacionado
                    $q->orWhereHas('educador', function ($q2) use ($value) {
                        $q2->where('nome', 'like', "%{$value}%");
                    });
                });
            }

            // Filtros opcionais
            if ($request->filled('finalizada')) {
                $query->where('finalizada', $request->input('finalizada'));
            }
            if ($request->filled('eliminada')) {
                $query->where('eliminada', $request->input('eliminada'));
            }

            $query->orderByDesc('idTurma');
            $turmas = $query->paginate($items, ['*'], 'page', $page);

            return response()->json([
                'message' => 'Turmas carregadas com sucesso.',
                'body' => $turmas->items(),
                'paginacao' => [
                    'totalPages' => $turmas->lastPage(),
                    'totalItems' => $turmas->total(),
                    'items' => $turmas->perPage(),
                    'page' => $turmas->currentPage(),
                ]
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'Erro ao buscar turmas.',
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    // Buscar turma específica
    public function getTurma($id)
    {
        try {
            $turma = Turma::with(['educador', 'usuarioRegistro', 'alunos'])->where('idTurma', $id)->firstOrFail();

            return response()->json([
                'message' => 'Turma carregada com sucesso.',
                'body' => $turma,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Turma não encontrada.',
            ], 404);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'Erro ao buscar turma.',
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    // Atualizar turma
    public function updateTurma(Request $request)
    {
        try {
            $turma = Turma::where('idTurma', $request->input('idTurma'))->firstOrFail();

            $validator = $request->validate([
                'nome' => 'sometimes|string|max:255',
                'idEducador' => 'sometimes|exists:tb_usuario,idUsuario',
                'faixaEtariaMin' => ['sometimes', 'string', 'regex:/^\d{1,2}(m|a)$/'],
                'faixaEtariaMax' => ['nullable', 'string', 'regex:/^\d{1,2}(m|a)$/'],
                'capacidade' => 'sometimes|integer|min:1|max:100',
                'dataInicio' => 'sometimes|date',
                'dataTermino' => 'nullable|date|after_or_equal:dataInicio',
                'finalizada' => 'sometimes|boolean',
                'eliminada' => 'sometimes|boolean',
                'cor' => 'sometimes|string|regex:/^#[0-9A-Fa-f]{6}$/',
            ], [
                'nome.string' => 'Nome deve ser uma string',
                'nome.max' => 'Nome deve ter no máximo 255 caracteres',
                'idEducador.exists' => 'Educador não encontrado',
                'faixaEtariaMin.string' => 'Faixa etária mínima deve ser uma string',
                'faixaEtariaMin.regex' => 'Faixa etária mínima deve ser um valor válido (ex: 2m, 1a)',
                'faixaEtariaMax.string' => 'Faixa etária máxima deve ser uma string',
                'faixaEtariaMax.regex' => 'Faixa etária máxima deve ser um valor válido (ex: 2m, 1a)',
                'capacidade.integer' => 'Capacidade deve ser um número inteiro',
                'capacidade.min' => 'Capacidade deve ser pelo menos 1',
                'capacidade.max' => 'Capacidade deve ser no máximo 100',
                'dataInicio.date' => 'Data de início deve ser uma data válida',
                'dataInicio.after' => 'Data de início deve ser posterior a hoje',
                'dataTermino.date' => 'Data de término deve ser uma data válida',
                'dataTermino.after_or_equal' => 'Data de término deve ser igual ou posterior à data de início',
                'finalizada.boolean' => 'Finalizada deve ser um valor booleano',
                'eliminada.boolean' => 'Eliminada deve ser um valor booleano',
                'cor.regex' => 'Cor deve ser um valor hexadecimal válido (ex: #FF5733)',
                'faixaEtariaMax.gte' => 'Faixa etária máxima deve ser maior ou igual à faixa etária mínima',
            ]);

            // Se não vier cor, sorteia uma nova (opcional)
            if (!isset($validator['cor'])) {
                $validator['cor'] = $this->gerarCorHex();
            }

            $qtdAlunosNaTurma = AlunoTurma::where('idTurma', $request->idTurma)
                ->where('terminado', false)
                ->count();

            // Se a turma já tem alunos, não pode reduzir a capacidade
            if (isset($validator['capacidade']) && $validator['capacidade'] < $qtdAlunosNaTurma) {
                return response()->json([
                    'message' => 'Não é possível reduzir a capacidade da turma, pois já existem ' . $qtdAlunosNaTurma . ' alunos  matriculados.'
                ], 422);
            }

            $turma->update($validator);

            return response()->json([
                'message' => 'Turma atualizada com sucesso!',
                'body' => $turma,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Turma não encontrada.',
            ], 404);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'Erro ao atualizar turma.',
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    // "Excluir" turma (soft delete)
    public function excluirTurma(Request $request, $id)
    {
        try {
            $turma = Turma::where('idTurma', $id)->firstOrFail();
            $turma->eliminada = true;
            $turma->save();

            return response()->json([
                'message' => 'Turma eliminada com sucesso!',
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Turma não encontrada.',
            ], 404);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'Erro ao eliminar turma.',
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    // Contagem de turmas
    public function countTurmas()
    {
        try {
            $total = Turma::count();
            $ativas = Turma::where('finalizada', 0)->where('eliminada', 0)->count();
            $finalizadas = Turma::where('finalizada', 1)->count();

            return response()->json([
                'message' => 'Contagem de turmas obtida com sucesso.',
                'body' => [
                    'total' => $total,
                    'ativas' => $ativas,
                    'finalizadas' => $finalizadas,
                ]
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'Erro ao contar turmas.',
                'message' => $th->getMessage(),
            ], 500);
        }
    }
}
