<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ACOPIO - Iniciar sesión</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: system-ui, -apple-system, sans-serif; background: #f1f5f9; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .card { background: #fff; border-radius: 16px; padding: 40px 32px; width: 100%; max-width: 380px; box-shadow: 0 10px 25px rgba(0,0,0,.08); }
        h1 { font-size: 24px; color: #0f172a; margin-bottom: 8px; }
        p { color: #64748b; font-size: 14px; margin-bottom: 28px; }
        .btn { display: block; width: 100%; text-align: center; text-decoration: none; padding: 12px 16px; border-radius: 10px; font-weight: 600; font-size: 15px; margin-bottom: 12px; transition: background .15s; }
        .btn-google { background: #fff; color: #1f2937; border: 1px solid #d1d5db; }
        .btn-google:hover { background: #f9fafb; }
        .btn-microsoft { background: #0f172a; color: #fff; }
        .btn-microsoft:hover { background: #1e293b; }
    </style>
</head>
<body>
    <div class="card">
        <h1>ACOPIO</h1>
        <p>Acceda con su correo corporativo para continuar.</p>
        <a class="btn btn-google" href="{{ route('auth.redirect', ['provider' => 'google']) }}">Continuar con Google</a>
        <a class="btn btn-microsoft" href="{{ route('auth.redirect', ['provider' => 'microsoft']) }}">Continuar con Microsoft (Outlook / Hotmail)</a>
    </div>
</body>
</html>
