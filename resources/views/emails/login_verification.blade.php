<!DOCTYPE html>
<html>
    <body>
        <div>
            <h3>Új bejelentkezés észlelve</h3>
            <p>Egy új bejelentkezést észleltünk a Jedligram fiókodon.</p>

            <p>A bejelenzés megerősítéséhez kérjük, kattints a lenti gombra:</p>
            <x-button href="{{ env('FRONTEND_URL') }}/auth/verification?email={{ $email }}&token={{ $verificationCode }}">Bejelentkezés megerősítése</x-button>
            <p>Vagy írd be a következő kódot a weboldalunkon:</p>
            <p> {{ $verificationCode }}</p>
            <hr>
            <p>Amennyiben nem te kezdeményezted ezt a műveletet, kérjük, hagyd figyelmen kívül ezt az emailt.</p>

            {{-- <p><strong>Részletek:</strong></p>
            <ul>
                <li><strong>Időpont:</strong> {{ now()->toDayDateTimeString() }}</li>
                <li><strong>Eszköz:</strong> {{ $agent->platform() }} - {{ $agent->browser() }}</li>
                <li><strong>IP-cím:</strong> {{ $ipAddress }}</li>
            </ul> --}}
            
            
            <p>Üdvözlettel,<br>A Jedligram csapata</p>
        </div>
    </body>
</html>