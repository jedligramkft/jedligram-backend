<div>
    <form method="POST" action="/sysadmin/migrate">
        <p>Új migrációk futtatása</p>
        @csrf
        <button type="submit">migrate</button>
    </form>

    <form method="POST" action="/sysadmin/migrate_rollback">
        <p>Utolsó migráció visszavonása</p>
        @csrf
        <button type="submit">migrate:rollback</button>
    </form>
    
    <form method="POST" action="/sysadmin/migrate_fresh">
        <p>Adatbázis újragenerálása (minden adat törlődik)</p>
        @csrf
        <button type="submit">migrate:fresh</button>
    </form>

    <form method="POST" action="/sysadmin/migrate_fresh_seed">
        <p>Adatbázis újragenerálása adatokkal (minden adat törlődik, majd újra létre lesznek hozva a seed-ek alapján)</p>
        @csrf
        <button type="submit">migrate:fresh --seed</button>
    </form>
</div>
