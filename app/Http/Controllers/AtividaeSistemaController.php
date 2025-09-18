<?php

namespace App\Http\Controllers;

use App\Models\AtividadeSistemaNotificacao;
use Illuminate\Http\Request;

class AtividaeSistemaController extends Controller
{
    public function index(Request $request)
    {
        try {
            $atividades = AtividadeSistemaNotificacao::query()->orderByDesc('idAtividadeSistemaNotificacao')
                ->limit(11)
                ->get();

            return response()->json([
                'body' => $atividades
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'Erro interno!',
                'message' => $th->getMessage(),
            ], 500);
        }
    }
}
