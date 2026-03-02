<div>
    <form method="POST" action="/sysadmin/run_command?cmd=db:seed+--class=ProductionDataSeeder" data-ajax>
        <p>Azok az adatok, amik <b><u>éles</u></b> környezetben is jelen kell hogy legyenek</p>
        @csrf
        <button type="submit">Production seeder</button>
    </form>

    <form method="POST" action="/sysadmin/run_command?cmd=db:seed+--class=DummyDataSeeder" data-ajax>
        <p>Azok az adatok, amik <b><u>teszt</u></b> környezetben kellenek</p>
        @csrf
        <button type="submit">Dummy seeder</button>
    </form>
</div>
