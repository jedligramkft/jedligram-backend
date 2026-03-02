<style>
    #database_actions {
        min-width: 40%;
        max-width: 40%;
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        justify-content: center;
        align-items: center;
    }

    #database_actions form {
        /* background-color: green; */
        padding: 10px;
        display: flex;
        flex-direction: column;
        gap: 10px;
        width: 300px;
        flex-grow: 1;
        border: 1px solid #555;
    }
    
    #database_actions form button {
        /* background-color: red; */
        background-color: #35316d;
        padding: 6px;
        color: white;
        font-size: 16px;
        cursor: pointer;
    }

</style>
<div id="database_actions">
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
