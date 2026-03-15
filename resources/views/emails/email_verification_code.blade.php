<div>
    <p>Kedves Felhasználó!</p>
    <p>Köszönjük, hogy regisztráltál a Jedligramra! Az alábbi kódot használd a regisztrációd megerősítéséhez:</p>
    <a href="{{ env('FRONTEND_URL') }}/verifyemail?email={{ $email }}?token={{ $verificationCode }}" style="display: inline-block; padding: 10px 20px; color: #fff; background-color: #007BFF; text-decoration: none; border-radius: 5px;">Megerősítés</a>
    <h5>{{ $verificationCode }}</h5>
    <p>Ez a kód 15 percig érvényes. Kérjük, ne oszd meg másokkal!</p>
    <p>Üdvözlettel,<br>A Jedligram csapata</p>
</div>
