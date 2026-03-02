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
                background-color: #222;
                color: #eee;
            }

            #console_output {
                position: fixed;
                right: 0;
                top: 0;

                background-color: black;
                width: 50%;
                max-width: 50%;
                min-height: 100dvh; 

                color: #eee;
                padding: 10px;
            }

            button{
                outline: none;
                border: none;
            }

        </style>
    </head>
    <body>
        <h1>System administration page</h1>
        @if(session('sysadmin_authed'))
            <p style="color:lightgreen;">Logged in</p>

            <form method="POST" action="/logout">
                @csrf
                <button type="submit" style="background-color:darkred;color:white;padding: 10px 40px;cursor:pointer;">Log out</button>
            </form>
            
            <a href="https://jcloud02.jedlik.eu/phpmyadmin/index.php" 
                style="background-color:darkgreen;color:white;padding: 10px 20px; margin-top: 20px; display: inline-block; outline: none; text-decoration: none;"
                target="_blank">
                Open phpMyAdmin
            </a>

            <h3>Adatbázis műveletek</h3>
            @include('sysadmin.DatabaseActions')

            <h3>Feltöltők</h3>

            <form method="POST" action="/sysadmin/production_seeder">
                <p>Azok az adatok, amik <b><u>éles</u></b> környezetben is jelen kell hogy legyenek</p>
                @csrf
                <button type="submit" style="background-color: #35316d;padding: 6px;color: white;font-size: 16px;cursor: pointer;">Production seeder</button>
            </form>

            <form method="POST" action="/sysadmin/dummy_seeder">
                <p>Azok az adatok, amik <b><u>teszt</u></b> környezetben kellenek</p>
                @csrf
                <button type="submit" style="background-color: #35316d;padding: 6px;color: white;font-size: 16px;cursor: pointer;">Dummy seeder</button>
            </form>

            <div id="console_output">{{ session('console_output') }}</div>
        @else
            @include('auth.login')
        @endif
    </body>
</html>