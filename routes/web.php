<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
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
    $caminho = Illuminate\Support\Facades\Storage::disk('local')->putFile('uploads', $request->file('arquivo'));

    if ($caminho) {
        return 'Arquivo enviado e salvo com sucesso! Caminho: ' . $caminho;
    }

    return 'Falha ao salvar o arquivo.';
});

require __DIR__.'/auth.php';
