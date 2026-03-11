<div>
    

    <form method="POST" action="/sysadmin/run_command?cmd=migrate" data-ajax>
        <p>Új migrációk futtatása</p>
        @csrf
        <button type="submit">migrate</button>
        <input type="hidden" name="force" value="0">
        <label style="font-size:90%; margin-right:8px;"><input type="checkbox" name="force" value="1"> Force</label>
    </form>

    <form method="POST" action="/sysadmin/run_command?cmd=migrate:rollback" data-ajax>
        <p>Utolsó migráció visszavonása</p>
        @csrf
        <button type="submit">migrate:rollback</button>
        <input type="hidden" name="force" value="0">
        <label style="font-size:90%; margin-right:8px;"><input type="checkbox" name="force" value="1"> Force</label>
    </form>
    
    <form method="POST" action="/sysadmin/run_command?cmd=migrate:fresh" data-ajax>
        <p>Adatbázis újragenerálása (minden adat törlődik)</p>
        @csrf
        <button type="submit">migrate:fresh</button>
        <input type="hidden" name="force" value="0">
        <label style="font-size:90%; margin-right:8px;"><input type="checkbox" name="force" value="1"> Force</label>
    </form>

    <form method="POST" action="/sysadmin/run_command?cmd=migrate:fresh+--seed" data-ajax>
        <p>Adatbázis újragenerálása adatokkal (minden adat törlődik, majd újra létre lesznek hozva a seed-ek alapján)</p>
        @csrf
        <button type="submit">migrate:fresh --seed</button>
        <input type="hidden" name="force" value="0">
        <label style="font-size:90%; margin-right:8px;"><input type="checkbox" name="force" value="1"> Force</label>
    </form>
</div>
