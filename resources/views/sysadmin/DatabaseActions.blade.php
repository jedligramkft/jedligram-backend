<div>
    <form method="POST" action="/sysadmin/run_command?cmd=migrate" data-ajax>
        <p>Új migrációk futtatása</p>
        @csrf
        <button type="submit">migrate</button>
    </form>

    <form method="POST" action="/sysadmin/run_command?cmd=migrate:rollback" data-ajax>
        <p>Utolsó migráció visszavonása</p>
        @csrf
        <button type="submit">migrate:rollback</button>
    </form>
    
    <form method="POST" action="/sysadmin/run_command?cmd=migrate:fresh" data-ajax>
        <p>Adatbázis újragenerálása (minden adat törlődik)</p>
        @csrf
        <button type="submit">migrate:fresh</button>
    </form>

    <form method="POST" action="/sysadmin/run_command?cmd=migrate:fresh+--seed" data-ajax>
        <p>Adatbázis újragenerálása adatokkal (minden adat törlődik, majd újra létre lesznek hozva a seed-ek alapján)</p>
        @csrf
        <button type="submit">migrate:fresh --seed</button>
    </form>
</div>
