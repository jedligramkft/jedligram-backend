<!DOCTYPE html>
<html>
    <body>
        <div>
            <h3>Kedves {{$name}},</h3>

            <p>A két faktoros azonosítás kikapcsolásához kérjük, kattints a lenti gombra:</p>
            <x-button href="{{ env('FRONTEND_URL') }}/auth/verification?email={{ $email }}&token={{ $verificationCode }}">2FA azonosítás kikapcsolása</x-button>
            <p>Vagy írd be a következő kódot a weboldalunkon:</p>
            <p>{{ $verificationCode }}</p>
            <p>Ez a kód 15 percig érvényes. Kérjük, ne oszd meg másokkal!</p>
            
            <hr>
            <p>Amennyiben nem te kezdeményezted ezt a műveletet, kérjük, hagyd figyelmen kívül ezt az emailt.</p>
            
            <p>Üdvözlettel,<br>A Jedligram csapata</p>
        </div>
    </body>
</html>