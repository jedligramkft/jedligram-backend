<!doctype html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Jedligram - Sysadmin</title>

        <!-- Fonts -->
        <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">

        <!-- Styles -->
        <style>
            body {
                font-family: 'Nunito', sans-serif;
            }

            #console_output {
                position: fixed;
                right: 0;
                top: 0;

                background-color: black;
                width: 50%;
                max-width: 50%;
                min-height: 100dvh; 

                color: white;
                padding: 10px;
            }

        </style>
    </head>
    <body>
        <h1>System administration page</h1>
        @if(session('sysadmin_authed'))
            <p style="color:green;">logged in</p>

            <form method="POST" action="/logout">
                @csrf
                <button type="submit">Log out</button>
            </form>

            <h3>Adatbázis műveletek</h3>

            <form method="POST" action="/sysadmin/migrate">
                @csrf
                <button type="submit">Új migrációk futtatása (migrate)</button>
            </form>

            <form method="POST" action="/sysadmin/migrate_rollback">
                @csrf
                <button type="submit">Utolsó migráció visszavonása (migrate:rollback)</button>
            </form>

            <form method="POST" action="/sysadmin/migrate_up">
                @csrf
                <button type="submit">Egy migráció előre (migrate:up)</button>
            </form>

            <form method="POST" action="/sysadmin/migrate_down">
                @csrf
                <button type="submit">Egy migráció visszavonása (migrate:down)</button>
            </form>
            
            <form method="POST" action="/sysadmin/migrate_fresh">
                @csrf
                <button type="submit">Adatbázis újragenerálása (migrate:fresh)</button>
            </form>

            <form method="POST" action="/sysadmin/migrate_fresh_seed">
                @csrf
                <button type="submit">Adatbázis újragenerálása adatokkal (migrate:fresh --seed)</button>
            </form>

            <div id="console_output">{{ session('console_output') }}</div>
        @else
            @include('auth.login')
        @endif
    </body>
</html>