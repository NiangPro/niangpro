<?php

/** @var \Niang\Core\Scheduling\Schedule $schedule */

// Tâches planifiées : lancées par `./bin/niang schedule:run`, qu'une seule ligne cron appelle chaque
// minute sur le serveur :
//
//   * * * * * cd /chemin/vers/le/projet && php bin/niang schedule:run >> /dev/null 2>&1
//
// `./bin/niang schedule:list` affiche les tâches et leur prochaine exécution. Exemples :
//
// $schedule->command('queue:work')->everyMinute()->withoutOverlapping();
// $schedule->command('cache:clear')->dailyAt('03:00');
// $schedule->call(fn () => \App\Models\Post::query()->where('published', false)->delete())->weekly();
// $schedule->job(new \App\Jobs\SendWelcomeEmailJob('admin@example.com'))->weeklyOn(1, '08:00');
