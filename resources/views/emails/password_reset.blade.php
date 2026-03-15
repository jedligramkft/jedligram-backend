<div>
    <p>Kedves Felhasználó!</p>
    <p>Kaptunk egy kérést a jelszavad visszaállítására. Az alábbi kódot használd a jelszavad visszaállításához:</p>
    <a href="{{ env('FRONTEND_URL') }}/resetpassword?email={{ $email }}?token={{ $resetCode }}" style="display: inline-block; padding: 10px 20px; color: #fff; background-color: #007BFF; text-decoration: none; border-radius: 5px;">Jelszó visszaállítása</a>
    <h5>{{ $resetCode }}</h5>
    <p>Ez a kód 15 percig érvényes. Kérjük, ne oszd meg másokkal!</p>
    <p>Üdvözlettel,<br>A Jedligram csapata</p>
</div>
