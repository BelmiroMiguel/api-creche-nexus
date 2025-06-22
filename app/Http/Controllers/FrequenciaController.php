<?php

namespace App\Http\Controllers;

use App\Models\Frequencia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class FrequenciaController extends Controller
{
    // Criar frequência (entrada)
    public function registrarEntrada(Request $request)
    {
        $validator =  $request->validate([
            'idAlunoTurma' => 'required|exists:tb_aluno_turma,idAlunoTurma',
            'horaEntrada' => 'required|date_format:H:i',
            'dataFrequencia' => 'required|date',
            'observacaoEntrada' => 'nullable|string|max:255',
            'nomeResponsavelEntrega' => 'required|string|max:255',
        ], [
            'idAlunoTurma.required' => 'Campo aluno é obrigatório.',
            'idAlunoTurma.exists' => 'Aluno não encontrado.',
            'horaEntrada.required' => 'Hora de entrada é obrigatória.',
            'horaEntrada.date_format' => 'Hora de entrada deve estar no formato HH:MM.',
            'dataFrequencia.date' => 'Data de frequência deve ser uma data válida.',
            'observacaoEntrada.max' => 'Observação de entrada não pode exceder 255 caracteres.',
            'nomeResponsavelEntrega.max' => 'Nome do responsável pela entrega não pode exceder 255 caracteres.',
            'nomeResponsavelEntrega.required' => 'Nome do responsável é obrigatório.',
        ]);


        $frequenciaExistente = Frequencia::where('idAlunoTurma', $validator['idAlunoTurma'])
            ->whereDay('dataFrequencia', Carbon::parse($validator['dataFrequencia'])->day)
            ->whereMonth('dataFrequencia', Carbon::parse($validator['dataFrequencia'])->month)
            ->whereYear('dataFrequencia', Carbon::parse($validator['dataFrequencia'])->year)
            ->first();

        if ($frequenciaExistente) {
            $horaEntrada =  Carbon::parse($request->horaEntrada);
            $horaSaida =  Carbon::parse($frequenciaExistente->horaSaida);

            if ($frequenciaExistente->horaSaida && $horaSaida->lt($horaEntrada)) {
                return response()->json(['message' => 'Hora de entrada não pode ser posterior à hora de saída já registrada.'], 422);
            }

            $frequenciaExistente->update($validator);

            return response()->json([
                'message' => 'Entrada do aluno atualizada.',
                'body' => $frequenciaExistente
            ], 200);
        }

        $usuario = $request->user();
        if (!$usuario || !isset($usuario->idUsuario)) {
            return response()->json(['message' => 'Usuário autenticado não encontrado.'], 401);
        }
        $validator['idUsuarioRegistro'] = $usuario->idUsuario;

        $frequencia = Frequencia::create($validator);

        return response()->json([
            'message' => 'Entrada do aluno registrada.',
            'body' => $frequencia
        ], 201);
    }

    // Registrar saída
    public function registrarSaida(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'idFrequenciaAlunoTurma' => 'required|exists:tb_frequencia_aluno_turma,idFrequenciaAlunoTurma',
            'horaSaida' => 'required|date_format:H:i',
            'observacaoSaida' => 'nullable|string|max:255',
            'nomeResponsavelBusca' => 'required|string|max:255',
        ], [
            'idFrequenciaAlunoTurma.required' => 'Frequência não informada.',
            'idFrequenciaAlunoTurma.exists' => 'Frequência não encontrada.',
            'horaSaida.required' => 'Hora de saída é obrigatória.',
            'horaSaida.date_format' => 'Hora de saída deve estar no formato HH:MM.',
            'observacaoSaida.max' => 'Observação de saída não pode exceder 255 caracteres.',
            'nomeResponsavelBusca.max' => 'Nome do responsável pela busca não pode exceder 255 caracteres.',
            'nomeResponsavelBusca.required' => 'Nome do responsável é obrigatório.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $frequencia = Frequencia::findOrFail($request->idFrequenciaAlunoTurma);

        $horaEntrada =  Carbon::parse($frequencia->horaEntrada);
        $horaSaida =  Carbon::parse($request->horaSaida);


        if ($horaEntrada && $horaSaida->lt($horaEntrada)) {
            return response()->json(['message' => 'Hora de saída não pode ser anterior à hora de entrada.'], 422);
        }

        $frequencia->update($validator->validated());

        return response()->json([
            'message' => 'Saída registrada com sucesso.',
            'body' => $frequencia
        ], 200);
    }

    // Atualizar frequência completa (opcional)
    public function atualizarFrequencia(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'idFrequenciaAlunoTurma' => 'required|exists:tb_frequencia_aluno_turma,idFrequenciaAlunoTurma',
            'idAlunoTurma' => 'sometimes|exists:tb_aluno_turma,idAlunoTurma',
            'horaEntrada' => 'sometimes|date_format:H:i',
            'horaSaida' => 'sometimes|date_format:H:i',
            'dataFrequencia' => 'sometimes|date',
            'observacaoEntrada' => 'sometimes|string|max:255',
            'observacaoSaida' => 'sometimes|string|max:255',
            'nomeResponsavelEntrega' => 'sometimes|string|max:255',
            'nomeResponsavelBusca' => 'sometimes|string|max:255',
        ], [
            'idFrequenciaAlunoTurma.required' => 'Frequência não informada.',
            'idFrequenciaAlunoTurma.exists' => 'Frequência não encontrada.',
            'horaEntrada.date_format' => 'Hora de entrada deve estar no formato HH:MM.',
            'horaSaida.date_format' => 'Hora de saída deve estar no formato HH:MM.',
            'dataFrequencia.date' => 'Data de frequência deve ser uma data válida.',
            'observacaoEntrada.max' => 'Observação de entrada não pode exceder 255 caracteres.',
            'observacaoSaida.max' => 'Observação de saída não pode exceder 255 caracteres.',
            'nomeResponsavelEntrega.max' => 'Nome do responsável pela entrega não pode exceder 255 caracteres.',
            'nomeResponsavelBusca.max' => 'Nome do responsável pela busca não pode exceder 255 caracteres.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $frequencia = Frequencia::findOrFail($request->idFrequenciaAlunoTurma);
        $dados = $validator->validated();

        // Checa se horaSaida < horaEntrada
        if (isset($dados['horaEntrada']) && isset($dados['horaSaida'])) {
            $entrada = Carbon::createFromFormat('H:i', $dados['horaEntrada']);
            $saida = Carbon::createFromFormat('H:i', $dados['horaSaida']);

            if ($saida->lt($entrada)) {
                return response()->json([
                    'message' => 'Hora de saída não pode ser anterior à hora de entrada.'
                ], 422);
            }
        }

        $frequencia->update($dados);

        return response()->json([
            'message' => 'Frequência atualizada com sucesso.',
            'body' => $frequencia
        ], 200);
    }

    private function parseHora($hora)
    {
        try {
            return Carbon::createFromFormat('H:i', $hora);
        } catch (\Exception $e1) {
            try {
                return Carbon::createFromFormat('H:i:s', $hora);
            } catch (\Exception $e2) {
                throw new \InvalidArgumentException("Formato de hora inválido. Use HH:MM ou HH:MM:SS." . $hora);
            }
        }
    }
}
