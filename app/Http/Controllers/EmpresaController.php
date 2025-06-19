<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;

class EmpresaController extends Controller
{
    public function createEmpresa(Request $request)
    {
        try {
            $empresa = Empresa::first();

            if ($empresa) {
                return response()->json([
                    'body' => $empresa,
                    'message' => 'Já existe uma Empresa'
                ], 403);
            }

            // Validação dos dados
            $validated = $request->validate([
                'nome' => 'required|string|max:150',
                'nif' => 'required|string|max:50',
                'telefone' => 'required|string|max:20',
                'endereco' => 'required|string|max:255',
                'email' => 'required|email|unique:tb_empresa,email',
            ], [
                'nome.required' => 'O nome da empresa é obrigatório.',
                'nome.max' => 'O nome da empresa deve ter no máximo 150 caracteres.',
                'nif.required' => 'O nif da empresa é obrigatório.',
                'nif.max' => 'O nif da empresa deve ter no máximo 50 caracteres.',
                'email.required' => 'O email da empresa é obrigatório.',
                'email.email' => 'O email informado não é válido.',
                'email.unique' => 'Este email já está cadastrado para outra empresa.',
                'telefone.required' => 'O telefone da empresa é obrigatório.',
                'telefone.max' => 'O telefone da empresa deve ter no máximo 20 caracteres.',
                'endereco.required' => 'O endereço da empresa é obrigatório.',
                'endereco.max' => 'O endereço da empresa deve ter no máximo 255 caracteres.',
            ]);

            // Criação da empresa
            $empresa = Empresa::create($validated);

            return response()->json([
                'message' => 'Empresa criada com sucesso!',
                'body' => $empresa,
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'Erro ao criar empresa.',
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    // Método para atualizar os dados de uma empresa existente
    public function updateEmpresa(Request $request,)
    {
        try {
            // Buscar a empresa
            $empresa = Empresa::first();

            if (!$empresa) {
                return response()->json(['message' => 'Empresa não encontrada'], 404);
            }

            // Validação dos dados
            $validated = $request->validate([
                'nome' => 'sometimes|required|string|max:150',
                'nif' => 'sometimes|required|string|max:50',
                'telefone' => 'sometimes|required|string|max:20',
                'endereco' => 'sometimes|required|string|max:255',
                'email' => 'sometimes|required|email|unique:tb_empresa,email,' . $empresa->idEmpresa . ',idEmpresa',
            ], [
                'nome.required' => 'O nome da empresa é obrigatório.',
                'nome.max' => 'O nome da empresa deve ter no máximo 150 caracteres.',
                'nif.required' => 'O nif da empresa é obrigatório.',
                'nif.max' => 'O nif da empresa deve ter no máximo 50 caracteres.',
                'email.required' => 'O email da empresa é obrigatório.',
                'email.email' => 'O email informado não é válido.',
                'email.unique' => 'Este email já está cadastrado para outra empresa.',
                'telefone.required' => 'O telefone da empresa é obrigatório.',
                'telefone.max' => 'O telefone da empresa deve ter no máximo 20 caracteres.',
                'endereco.required' => 'O endereço da empresa é obrigatório.',
                'endereco.max' => 'O endereço da empresa deve ter no máximo 255 caracteres.',
            ]);

            // Verifica se foi enviada uma nova imagem
            if ($request->hasFile('imagem')) {
                $file = $request->file('imagem');
                $filename = 'empresa_' . $empresa->idEmpresa . '_' . time() . '.' . $file->getClientOriginalExtension();
                $destinationPath = public_path('uploads/imagens/empresas');
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0755, true); // cria a pasta se não existir
                }
                $file->move($destinationPath, $filename);
                $validated['imagem'] =  $filename;
            }

            // Atualizar dados da empresa
            $empresa->update($validated);

            return response()->json([
                'message' => 'Empresa atualizada com sucesso!',
                'body' => $empresa,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Empresa não encontrada.',
            ], 404);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'Erro ao atualizar empresa.',
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function getEmpresa()
    {
        $empresa = Empresa::first();

        if (!$empresa) {
            return response()->json(['message' => 'Empresa não encontrada'], 404);
        }

        return response()->json(['body' => $empresa], 200);
    }
}
