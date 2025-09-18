<?php

use App\Http\Controllers\AlunoController;
use App\Http\Controllers\AtividaeSistemaController;
use App\Http\Controllers\ConfirmacaoController;
use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\FrequenciaController;
use App\Http\Controllers\MensalidadePagamentoController;
use App\Http\Controllers\TurmaController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::middleware('auth:sanctum')->prefix('empresas')->group(function () {
    Route::post('/', [EmpresaController::class, 'createEmpresa']);
    Route::put('/', [EmpresaController::class, 'updateEmpresa']);
    Route::get('/', [EmpresaController::class, 'getEmpresa']);
});


Route::prefix('usuarios')->group(function () {
    Route::post('/', [UsuarioController::class, 'cadastrarUsuario']);
    Route::post('/login', [UsuarioController::class, 'login']);

    Route::get('/imagem/{imagem}', [UsuarioController::class, 'getImagemUsuario']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/editar', [UsuarioController::class, 'updateUsuario']);
        Route::get('/', [UsuarioController::class, 'getUsuarios']);
        Route::get('/id', [UsuarioController::class, 'getById']);
        Route::get('/count', [UsuarioController::class, 'countUsuarios']);
    });
});


Route::prefix('alunos')->group(function () {
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/', [AlunoController::class, 'cadastrarAluno']);
        Route::post('/editar', [AlunoController::class, 'updateAluno']);
        Route::get('/', [AlunoController::class, 'getAlunos']);
        Route::get('/count', [AlunoController::class, 'countAlunos']);
        Route::get('/ultimos-meses', [AlunoController::class, 'getAlunosResumoUltimosMeses']);
        Route::get('/{id}', [AlunoController::class, 'getAluno']);
    });

    Route::get('/imagem/{imagem}', [AlunoController::class, 'getImagemAluno']);
});

Route::prefix('turmas')->middleware('auth:sanctum')->group(function () {
    Route::post('/', [TurmaController::class, 'cadastrarTurma']);
    Route::post('/editar', [TurmaController::class, 'updateTurma']);
    Route::get('/', [TurmaController::class, 'getTurmas']);
    Route::get('/count', [TurmaController::class, 'countTurmas']);
    Route::get('/{id}', [TurmaController::class, 'getTurma']);
    Route::delete('/{id}', [TurmaController::class, 'excluirTurma']);
});

Route::prefix('confirmacoes')->middleware('auth:sanctum')->group(function () {
    Route::post('/', [ConfirmacaoController::class, 'confirmar']);
    Route::post('/encerrar', [ConfirmacaoController::class, 'encerrar']);
    Route::post('/trocar-turma', [ConfirmacaoController::class, 'trocarTurma']);
    Route::get('/', [ConfirmacaoController::class, 'getConfirmacoesPorTurma']);
});



Route::prefix('frequencia')->middleware('auth:sanctum')->group(function () {
    Route::post('/entrada', [FrequenciaController::class, 'registrarEntrada']);
    Route::post('/saida', [FrequenciaController::class, 'registrarSaida']);
    Route::post('/atualizar', [FrequenciaController::class, 'atualizarFrequencia']);
});


Route::middleware('auth:sanctum')->prefix('mensalidades')->group(function () {
    Route::get('/', [MensalidadePagamentoController::class, 'getMensalidaeFaixaEtaria']);
    Route::put('/', [MensalidadePagamentoController::class, 'atualizarMensalidaeFaixaEtaria']);
});


Route::middleware('auth:sanctum')->prefix('propinas')->group(function () {
    Route::get('/', [MensalidadePagamentoController::class, 'getAlunosMensalidades']);
    Route::get('/count', [MensalidadePagamentoController::class, 'countAlunosMensalidades']);
    Route::post('/', [MensalidadePagamentoController::class, 'pagarMensalidade']);
});

Route::middleware('auth:sanctum')->prefix('atividades')->group(function () {
    Route::get('/', [AtividaeSistemaController::class, 'index']);
});



Route::get('/user', function (Request $request) {
    return $request->user();
});
