<?php
use Illuminate\Support\Facades\Schedule;
Schedule::command('astra:sync')->everyMinute()->withoutOverlapping();
Schedule::command('astra:sessions-sync')->everyMinute()->withoutOverlapping();
Schedule::command('subscribers:expire')->everyMinute()->withoutOverlapping();
Schedule::command('epg:import')->hourly()->withoutOverlapping(120);

\Illuminate\Support\Facades\Schedule::command('alerts:check')->everyMinute()->withoutOverlapping(2);

\Illuminate\Support\Facades\Schedule::command('operations:track')->everyMinute()->withoutOverlapping(2);
