<div>
    <form method="POST" action="/sysadmin/run_command?cmd=db:seed+--class=ProductionDataSeeder" data-ajax>
        <p>Azok az adatok, amik <b><u>éles</u></b> környezetben is jelen kell hogy legyenek</p>
        @csrf
        <button type="submit">Production seeder</button>
        <input type="hidden" name="force" value="0">
        <label style="font-size:90%; margin-right:8px;"><input type="checkbox" name="force" value="1"> Force</label>
    </form>

    <form method="POST" action="/sysadmin/run_command?cmd=db:seed+--class=DummyDataSeeder" data-ajax>
        <p>Azok az adatok, amik <b><u>teszt</u></b> környezetben kellenek</p>
        @csrf
        <button type="submit">Dummy seeder</button>
        <input type="hidden" name="force" value="0">
        <label style="font-size:90%; margin-right:8px;"><input type="checkbox" name="force" value="1"> Force</label>
    </form>
</div>
