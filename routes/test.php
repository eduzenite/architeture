<?php

if (app()->environment() !== 'local') {
    return;
}

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;

Route::get('/mailhog', function () {
    Mail::raw('Este é um email de teste enviado para o MailHog.', function ($message) {
        $message->to('destinatario@teste.com')
            ->subject('Teste com MailHog 🚀');
    });

    return 'Email enviado! Verifique no MailHog (http://127.0.0.1:8025).';
});

Route::get('/log', function () {
    Illuminate\Support\Facades\Log::info('📝 Teste de log gerado manualmente!');

    return 'Log gerado com sucesso! Veja em storage/logs/laravel.log';
});

// Rota para exibir o formulário
Route::get('/upload-file-sem-blade', function (Illuminate\Http\Request $request) {
    $errors = session('errors', new Illuminate\Support\MessageBag);
    $csrfToken = csrf_token();

    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <title>Upload de Arquivo (Sem Blade)</title>
    </head>
    <body>
        <h1>Upload de Arquivo</h1>';

    if ($errors->has('arquivo')) {
        $html .= '<div style="color: red;">' . $errors->first('arquivo') . '</div>';
    }

    $html .= '
        <form action="/upload-file-sem-blade" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="_token" value="' . $csrfToken . '">
            <label for="arquivo">Selecione o arquivo:</label>
            <input type="file" name="arquivo" id="arquivo">
            <br><br>
            <button type="submit">Enviar</button>
        </form>
    </body>
    </html>';

    return $html;
});

// Rota para processar o upload
Route::post('/upload-file-sem-blade', function (Illuminate\Http\Request $request) {
    // Valida o arquivo
    $request->validate([
        'arquivo' => 'required|file|max:10240', // até 10MB
    ]);

    // Salva localmente no storage/app/uploads
    $caminho = Illuminate\Support\Facades\Storage::putFile('uploads', $request->file('arquivo'));

    if ($caminho) {
        return 'Arquivo enviado e salvo com sucesso! Caminho: ' . $caminho;
    }

    return 'Falha ao salvar o arquivo.';
});
