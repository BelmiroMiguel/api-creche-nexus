<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\Usuario;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UsuarioController extends Controller
{
    //


    public function cadastrarUsuario(Request $request)
    {
        try {
            //validar
            $validator = $request->validate([
                'nome' => 'required|string|max:255',
                'email' => 'required|email|unique:tb_usuario,email',
                'telefone' => 'required|string|unique:tb_usuario,telefone',
                'funcao' => 'required|string',
                'nivel' => 'required|string|in:admin,educador,usuario,auxiliar,gestao',
                'endereco' => 'required|string|max:255',
                'genero' => 'required|string|in:m,f',
                'dataNascimento' => 'required|date',
            ], [
                'nome.required' => 'Campo nome é obrigatório',
                'email.required' => 'Campo email é obrigatório',
                'email.unique' => 'Este e-mail já está em uso.',
                'telefone.required' => 'Campo telefone é obrigatório',
                'telefone.unique' => 'Este telefone já está em uso.',
                'funcao.required' => 'Campo função é obrigatório',
                'nivel.required' => 'Campo nível é obrigatório',
                'nivel.in' => 'O nível de usuário deve ser "admin", "educador", "auxiliar", "gestao" ou "usuario".',
                'endereco.required' => 'Campo endereço é obrigatório',
                'endereco.max' => 'O endereço não pode exceder 255 caracteres.',
                'senha.min' => 'Senha demasiado curta.',
                'genero.required' => 'Campo gênero é obrigatório',
                'genero.in' => 'O gênero deve ser "m" (masculino) ou "f" (feminino).',
            ]);

            // Se uma imagem foi enviada, armazene-a e salve o caminho
            if ($request->hasFile('imagem')) {
                $file = $request->file('imagem');
                $filename = uniqid('usuario_') . '.' . $file->getClientOriginalExtension();
                $destinationPath = public_path('uploads/imagens');
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }
                $file->move($destinationPath, $filename);
                $validator['srcImagem'] = $filename;
            }

            $validator['senha'] = bcrypt('1234');

            $usuario = Usuario::create($validator);

            return response()->json([
                'message' => 'Usuário cadastrado com sucesso!',
                'body' =>  $usuario,
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'Erro interno!',
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'senha' => 'required',
            ], [
                'email.required' => 'Campo email é obrigatório',
                'email.email' => 'Formato de email inválido',
                'senha.required' => 'Campo senha é obrigatório',
            ]);

            $usuario = Usuario::where('email', $request->input('email'))->first();

            if (!$usuario || !Hash::check($request->input('senha'), $usuario->senha)) {
                return response()->json([
                    'message' => 'Usuário ou senha inválidos ',
                ], 403);
            }

            $empresa = Empresa::first();
            $usuario['empresa'] = $empresa;

            //gerar token
            $token = $usuario->createToken('token')->plainTextToken;

            return response()->json([
                'message' => 'Login realizado com sucesso!',
                'token' => $token,
                'body' => $usuario,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Erro de validação',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function getUsuarios(Request $request)
    {
        try {
            $items = $request->input('items', 15);
            $page = $request->input('page', 1);

            $filterableFields = [
                'eliminado',
            ];

            $searchableFields = ['nome', 'email', 'telefone', 'funcao'];
            $value = $request->input('value'); // parâmetro de busca textual
            $query = Usuario::query();

            // Aplica filtro de busca genérica (nome, email, telefone, funcao)
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

            // Ordenação e paginação
            $query->orderByDesc('idUsuario');
            if ($items == -1) {
                $usuarios = $query->get();
            } else {
                $usuarios = $query->paginate($items, ['*'], 'page', $page);
                $paginacao = [
                    'totalPages' => $usuarios->lastPage(),
                    'totalItems' => $usuarios->total(),
                    'items' => $usuarios->perPage(),
                    'page' => $usuarios->currentPage(),
                ];
                $usuarios = $usuarios->items();
            }

            return response()->json([
                'message' => 'Usuários carregados com sucesso.',
                'body' => $usuarios,
                'paginacao' => $paginacao ?? null
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'Erro interno!',
                'message' => $th->getMessage(),
            ], 500);
        }
    }


    public function updateUsuario(Request $request)
    {
        try {
            $usuario = Usuario::where('idUsuario', $request->input('idUsuario'))->first();

            $validator = $request->validate([
                'nome' => 'sometimes|required|string|max:255',
                'email' => 'sometimes|required|email|unique:tb_usuario,email,' . $usuario->idUsuario . ',idUsuario',
                'telefone' => 'sometimes|required|string|unique:tb_usuario,telefone,' . $usuario->idUsuario . ',idUsuario',
                'senha' => 'sometimes|required|string|min:4',
                'funcao' => 'sometimes|required|string',
                'dataCadastro' => 'sometimes|date',
                'eliminado' => 'sometimes|boolean',
                'acessoSistema' => 'sometimes|boolean',
                'nivel' => 'sometimes|required|string|in:admin,educador,usuario,auxiliar,gestao',
                'endereco' => 'sometimes|required|string|max:255',
                'genero' => 'sometimes|required|string|in:m,f',
                'dataNascimento' => 'sometimes|date',

            ], [
                'email.unique' => 'Este e-mail já está em uso.',
                'telefone.unique' => 'Este telefone já está em uso.',
                'senha.min' => 'Senha demasiado curta.',
                'nivel.in' => 'O nível de usuário deve ser "admin", "educador", "auxiliar", "gestao" ou "usuario".',
                'genero.in' => 'O gênero deve ser "m" (masculino) ou "f" (feminino).',
                'endereco.max' => 'O endereço não pode exceder 255 caracteres.',
            ]);

            // Se uma imagem foi enviada, armazene-a e salve o caminho
            if ($request->hasFile('imagem')) {
                // pega aimagem antiga para ser eliminada posteriormente
                $oldImage = $usuario->srcImagem ?? null;

                $file = $request->file('imagem');
                $filename = uniqid('usuario_') . '.' . $file->getClientOriginalExtension();
                $destinationPath = public_path('uploads/imagens');
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }
                $file->move($destinationPath, $filename);
                $validator['srcImagem'] = $filename;

                // elimina a imagem antiga apois salvar outra
                if ($oldImage && file_exists($destinationPath . '/' . $oldImage)) {
                    unlink($destinationPath . '/' . $oldImage);
                }
            }

            // atualiza apenas os campos enviados
            if (isset($validator['senha'])) {
                $validator['senha'] = bcrypt($validator['senha']);
            }

            $usuario->update($validator);

            return response()->json([
                'message' => 'Usuário atualizado com sucesso!',
                'body' => $usuario,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Usuário não encontrado.',
            ], 404);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'Erro ao atualizar usuário.',
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function getImagemUsuario($imagem)
    {
        try {
            $path = public_path('uploads/imagens/' . $imagem);

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
                'error' => 'Usuário não encontrado.'
            ], 404);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'Erro ao buscar imagem.',
                'message' => $th->getMessage(),
            ], 500);
        }
    }


    public function countUsuarios()
    {
        try {
            $total = Usuario::count();
            $ativos = Usuario::where('eliminado', 0)->count();
            $inativos = Usuario::where('eliminado', 1)->count();

            return response()->json([
                'message' => 'Contagem de usuários obtida com sucesso.',
                'body' => [
                    'total' => $total,
                    'ativos' => $ativos,
                    'inativos' => $inativos,
                ]
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'Erro ao contar usuários.',
                'message' => $th->getMessage(),
            ], 500);
        }
    }
}
