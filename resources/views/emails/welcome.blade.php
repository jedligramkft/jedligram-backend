<!DOCTYPE html>
<html>
    <body>
        <h3>Kedves, {{ $name }}</h3>

        <p>Örülünk, hogy csatlakoztál a jedlikesek közösségünkhöz. Íme néhány gyors tipp a kezdéshez:</p>

        <ul>
            <li>Fedezd fel a legújabb bejegyzéseket és témákat.</li>
            <li>Köss kapcsolatot más felhasználókkal.</li>
            <li>Oszd meg gondolataidat és ötleteidet.</li>
        </ul>

        <x-button href="{{ env('FRONTEND_URL') }}">Látogasd meg a Jedligramot</x-button>

        <p>Ha bármilyen kérdésed van, fordulj bizalommal ügyfélszolgálatunkhoz.</p>

        <p>Üdvözlettel,<br>
        A Jedligram Csapata</p>
    </body>
</html>