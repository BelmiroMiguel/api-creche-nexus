<?php

namespace App\Http\Controllers;

use App\Models\Aluno;
use App\Models\Usuario;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AlunoController extends Controller
{
    public function cadastrarAluno(Request $request)
    {
        try {
            $validator = $request->validate([
                'nome' => 'required|string|max:255',
                'identificacao' => 'nullable|string|unique:tb_aluno,identificacao',
                'nomeResponsavel' => 'required|string|max:255',
                'identificacaoResponsavel' => 'required|string',
                'telefoneResponsavel' => 'required|string',
                'dataNascimento' => 'required|date',
                'endereco' => 'required|string|max:255',
                'observacao' => 'sometimes|string|nullable',
                'genero' => 'required|string|in:m,f',
                'emailResponsavel' => 'sometimes|email|max:255',
                'grauParentesco' => 'required|string|max:100',
            ], [
                'nome.required' => 'Campo nome é obrigatório',
                'nome.string' => 'O nome deve ser um texto.',
                'nome.max' => 'O nome não pode exceder 255 caracteres.',
                'identificacao.string' => 'A identificação deve ser um texto.',
                'identificacao.unique' => 'Esta identificação do aluno já está em uso.',
                'nomeResponsavel.required' => 'Campo nome do responsável é obrigatório',
                'nomeResponsavel.string' => 'O nome do responsável deve ser um texto.',
                'nomeResponsavel.max' => 'O nome do responsável não pode exceder 255 caracteres.',
                'identificacaoResponsavel.required' => 'Campo identificação do responsável é obrigatório',
                'identificacaoResponsavel.string' => 'A identificação do responsável deve ser um texto.',
                'telefoneResponsavel.required' => 'Campo telefone do responsável é obrigatório',
                'telefoneResponsavel.string' => 'O telefone do responsável deve ser um texto.',
                'dataNascimento.required' => 'Campo data de nascimento é obrigatório',
                'dataNascimento.date' => 'A data de nascimento deve ser uma data válida.',
                'endereco.required' => 'Campo endereço é obrigatório',
                'endereco.string' => 'O endereço deve ser um texto.',
                'endereco.max' => 'O endereço não pode exceder 255 caracteres.',
                'genero.required' => 'Campo gênero é obrigatório',
                'genero.in' => 'O gênero deve ser "m" (masculino) ou "f" (feminino).',
                'grauParentesco.required' => 'Campo grau de parentesco é obrigatório',
                'grauParentesco.string' => 'O grau de parentesco deve ser um texto.',
                'grauParentesco.max' => 'O grau de parentesco não pode exceder 100 caracteres.',
            ]);

            // Se uma imagem foi enviada, armazene-a e salve o caminho
            if ($request->hasFile('imagem')) {
                $file = $request->file('imagem');
                $filename = uniqid('aluno_') . '.' . $file->getClientOriginalExtension();
                $destinationPath = public_path('uploads/imagens/alunos');
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }
                $file->move($destinationPath, $filename);
                $validator['srcImagem'] = $filename;
            }

            $anoAtual = date('Y');
            $ultimoAluno = Aluno::whereYear('dataCadastro', $anoAtual)
                ->orderByDesc('idAluno')
                ->first();

            $sequencial = 1;
            if ($ultimoAluno && preg_match('/^' . $anoAtual . '(\d{4})$/', $ultimoAluno->matricula, $matches)) {
                $sequencial = intval($matches[1]) + 1;
            }

            $validator['matricula'] = $anoAtual . str_pad($sequencial, 4, '0', STR_PAD_LEFT);

            // Adiciona dados padrão
            $validator['dataCadastro'] = now();
            $validator['eliminado'] = false;

            // Verifica se o usuário autenticado existe
            $usuario = $request->user();
            if (!$usuario || !isset($usuario->idUsuario)) {
                return response()->json([
                    'message' => 'Usuário autenticado não encontrado.',
                ], 401);
            }
            $validator['idUsuarioRegistro'] = $usuario->idUsuario;


            $aluno = Aluno::create($validator);


            return response()->json([
                'message' => 'Aluno cadastrado com sucesso!',
                'body' => $aluno,
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'Erro interno!',
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function getAlunos(Request $request)
    {
        try {
            $items = $request->input('items', 15);
            $page = $request->input('page', 1);

            $filterableFields = [
                'eliminado',
                'dataCadastro',
            ];

            $searchableFields = [
                'nome',
                'identificacao',
                'nomeResponsavel',
                'identificacaoResponsavel',
                'matricula',
                'telefoneResponsavel',
                'dataNascimento'
            ];
            $value = $request->input('value');
            $query = Aluno::with([
                'usuarioRegistro',
                'confirmacoes.turma.educador',
                'confirmacoes.usuarioRegistro',
                'confirmacoes.usuarioTermino',
            ]);

            // Aplica filtro de busca genérica
            if ($value) {
                $query->where(function ($q) use ($searchableFields, $value) {
                    foreach ($searchableFields as $field) {
                        $q->orWhere($field, 'like', "%{$value}%")
                            ->orWhere($field, $value);
                    }
                });
            }

            // Aplica os demais filtros normalmente
            foreach ($filterableFields as $field) {
                if ($request->filled($field)) {
                    $query->where($field, $request->input($field));
                }
            }

            if ($request->filled('confirmacao_terminado')) {
                $confirmacaoTerminado = (int)$request->input('confirmacao_terminado');

                if ($confirmacaoTerminado === 0) {
                    // Confirmados → última confirmação com terminado = 0
                    $query->whereIn('idAluno', function ($sub) {
                        $sub->select('idAluno')
                            ->from('tb_aluno_turma as at')
                            ->whereIn('idAlunoTurma', function ($sub2) {
                                $sub2->selectRaw('MAX(idAlunoTurma)')
                                    ->from('tb_aluno_turma')
                                    ->groupBy('idAluno');
                            })
                            ->where('terminado', 0);
                    });
                } else {
                    // Não confirmados → sem nenhuma confirmação OU última com terminado = 1
                    $query->where(function ($q) {
                        $q->whereNotIn('idAluno', function ($sub) {
                            $sub->select('idAluno')
                                ->from('tb_aluno_turma as at')
                                ->whereIn('idAlunoTurma', function ($sub2) {
                                    $sub2->selectRaw('MAX(idAlunoTurma)')
                                        ->from('tb_aluno_turma')
                                        ->groupBy('idAluno');
                                })
                                ->where('terminado', 0);
                        });
                    });
                }
            }



            // Ordenação e paginação
            $query->orderByDesc('idAluno');
            $alunos = $query->paginate($items, ['*'], 'page', $page);

            return response()->json([
                'message' => 'Alunos carregados com sucesso.',
                'body' => $alunos->items(),
                'paginacao' => [
                    'totalPages' => $alunos->lastPage(),
                    'totalItems' => $alunos->total(),
                    'items' => $alunos->perPage(),
                    'page' => $alunos->currentPage(),
                ]
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'Erro interno!',
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function updateAluno(Request $request)
    {
        try {
            $aluno = Aluno::where('idAluno', $request->input('idAluno'))->first();

            $validator = $request->validate([
                'nome' => 'sometimes|string|max:255',
                'identificacao' => 'sometimes|string|unique:tb_aluno,identificacao,' . $aluno->idAluno . ',idAluno',
                'nomeResponsavel' => 'sometimes|string|max:255',
                'identificacaoResponsavel' => 'sometimes|string',
                'telefoneResponsavel' => 'sometimes|string',
                'emailResponsavel' => 'sometimes|email|max:255',
                'grauParentesco' => 'sometimes|string|max:100',
                'dataNascimento' => 'sometimes|date',
                'endereco' => 'sometimes|string|max:255',
                'observacao' => 'sometimes|string|nullable',
                'eliminado' => 'sometimes|boolean',
                'genero' => 'sometimes|required|string|in:m,f',
            ], [
                'nome.required' => 'Campo nome é obrigatório',
                'nome.string' => 'O nome deve ser um texto.',
                'nome.max' => 'O nome não pode exceder 255 caracteres.',
                'identificacao.required' => 'Campo identificação é obrigatório',
                'identificacao.string' => 'A identificação deve ser um texto.',
                'identificacao.unique' => 'Esta identificação do aluno já está em uso.',
                'nomeResponsavel.required' => 'Campo nome do responsável é obrigatório',
                'nomeResponsavel.string' => 'O nome do responsável deve ser um texto.',
                'nomeResponsavel.max' => 'O nome do responsável não pode exceder 255 caracteres.',
                'identificacaoResponsavel.required' => 'Campo identificação do responsável é obrigatório',
                'identificacaoResponsavel.string' => 'A identificação do responsável deve ser um texto.',
                'telefoneResponsavel.required' => 'Campo telefone do responsável é obrigatório',
                'telefoneResponsavel.string' => 'O telefone do responsável deve ser um texto.',
                'dataNascimento.required' => 'Campo data de nascimento é obrigatório',
                'dataNascimento.date' => 'A data de nascimento deve ser uma data válida.',
                'endereco.required' => 'Campo endereço é obrigatório',
                'endereco.string' => 'O endereço deve ser um texto.',
                'endereco.max' => 'O endereço não pode exceder 255 caracteres.',
                'eliminado.boolean' => 'O campo eliminado deve ser verdadeiro ou falso.',
                'idUsuarioRegistro.exists' => 'O usuário de registro informado não existe.',
                'genero.in' => 'O gênero deve ser "m" (masculino) ou "f" (feminino).',
                'emailResponsavel.email' => 'O e-mail do responsável deve ser um endereço de e-mail válido.',
                'emailResponsavel.max' => 'O e-mail do responsável não pode exceder 255 caracteres.',
                'grauParentesco.string' => 'O grau de parentesco deve ser um texto.',
                'grauParentesco.max' => 'O grau de parentesco não pode exceder 100 caracteres.',
            ]);

            // Se uma imagem foi enviada, armazene-a e salve o caminho
            if ($request->hasFile('imagem')) {
                // pega a imagem antiga para ser eliminada posteriormente
                $oldImage = $aluno->srcImagem ?? null;

                $file = $request->file('imagem');
                $filename = uniqid('aluno_') . '.' . $file->getClientOriginalExtension();
                $destinationPath = public_path('uploads/imagens/alunos');
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }
                $file->move($destinationPath, $filename);
                $validator['srcImagem'] = $filename;

                // elimina a imagem antiga após salvar outra
                if ($oldImage && file_exists($destinationPath . '/' . $oldImage)) {
                    unlink($destinationPath . '/' . $oldImage);
                }
            }

            $usuario = $request->user();
            if (!$usuario || !isset($usuario->idUsuario)) {
                return response()->json(['message' => 'Usuário autenticado não encontrado.'], 401);
            }

            $validator['idUsuarioRegistro'] = $usuario->idUsuario;

            $aluno->update($validator);

            return response()->json([
                'message' => 'Aluno atualizado com sucesso!',
                'body' => $aluno,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Aluno não encontrado.',
            ], 404);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'Erro ao atualizar aluno.',
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function getImagemAluno($imagem)
    {
        try {
            $path = public_path('uploads/imagens/alunos/' . $imagem);

            if (!file_exists($path)) {
                return response()->json([
                    'error' => 'Arquivo de imagem não encontrado no servidor.'
                ], 404);
            }

            $mimeType = mime_content_type($path);

            return response()->file($path, [
                'Content-Type' => $mimeType
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Aluno não encontrado.'
            ], 404);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'Erro ao buscar imagem.',
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function getAluno($id)
    {
        try {
            $aluno = Aluno::where('idAluno', $id)->firstOrFail();

            return response()->json([
                'message' => 'Aluno carregado com sucesso.',
                'body' => $aluno,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Aluno não encontrado.',
            ], 404);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'Erro ao buscar aluno.',
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function countAlunos()
    {
        try {
            $total = Aluno::count();
            $ativos = Aluno::where('eliminado', 0)->count();
            $inativos = Aluno::where('eliminado', 1)->count();

            return response()->json([
                'message' => 'Contagem de alunos obtida com sucesso.',
                'body' => [
                    'total' => $total,
                    'ativos' => $ativos,
                    'inativos' => $inativos,
                ]
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'Erro ao contar alunos.',
                'message' => $th->getMessage(),
            ], 500);
        }
    }
}
