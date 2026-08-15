<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ACOPIO - Autorizar acceso</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: system-ui, -apple-system, sans-serif; background: #f1f5f9; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .card { background: #fff; border-radius: 16px; padding: 40px 32px; width: 100%; max-width: 400px; box-shadow: 0 10px 25px rgba(0,0,0,.08); }
        h1 { font-size: 22px; color: #0f172a; margin-bottom: 8px; }
        p { color: #64748b; font-size: 14px; margin-bottom: 8px; }
        .client { font-weight: 600; color: #0f172a; }
        .row { display: flex; gap: 12px; margin-top: 24px; }
        .btn { flex: 1; text-align: center; padding: 12px 16px; border-radius: 10px; font-weight: 600; font-size: 15px; cursor: pointer; border: none; text-decoration: none; }
        .btn-approve { background: #dc2626; color: #fff; }
        .btn-approve:hover { background: #b91c1c; }
        .btn-deny { background: #e2e8f0; color: #0f172a; }
        .btn-deny:hover { background: #cbd5e1; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Autorizar acceso</h1>
        <p>La aplicación <span class="client">{{ $client->name }}</span> desea acceder a su cuenta.</p>
        <p>Usuario: <span class="client">{{ $user->name }}</span></p>
        @if (count($scopes) > 0)
            <p>Permisos solicitados:</p>
            <ul>
                @foreach ($scopes as $scope)
                    <li>{{ $scope->getId() }}</li>
                @endforeach
            </ul>
        @else
            <p>No se solicitan permisos adicionales.</p>
        @endif

        <div class="row">
            <form method="post" action="{{ route('passport.authorizations.authorize') }}">
                @csrf
                <input type="hidden" name="state" value="{{ $request->state }}">
                <input type="hidden" name="client_id" value="{{ $client->id }}">
                <input type="hidden" name="auth_token" value="{{ $authToken }}">
                <button class="btn btn-approve" type="submit" name="approve" value="1">Autorizar</button>
            </form>
            <form method="post" action="{{ route('passport.authorizations.authorize') }}">
                @csrf
                <input type="hidden" name="state" value="{{ $request->state }}">
                <input type="hidden" name="client_id" value="{{ $client->id }}">
                <input type="hidden" name="auth_token" value="{{ $authToken }}">
                <button class="btn btn-deny" type="submit" name="deny" value="1">Cancelar</button>
            </form>
        </div>
    </div>
</body>
</html>
