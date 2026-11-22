<?php

namespace App\Console\Commands;

use Database\Seeders\CarpentrySeeder;
use Illuminate\Console\Command;

class DemoLocalCommand extends Command
{
    protected $signature = 'app:demo-local
                            {--fresh : Ejecuta migrate:fresh antes de cargar los ejemplos}';

    protected $description = 'Prepara datos de demostración locales (muebles, trabajos, materiales y sobrantes).';

    public function handle(): int
    {
        if (! app()->environment('local')) {
            $this->error('Este comando solo puede ejecutarse en entorno local.');

            return self::FAILURE;
        }

        if ($this->option('fresh')) {
            $this->warn('Recreando base de datos local…');
            $this->call('migrate:fresh', ['--force' => true]);
        } else {
            $this->call('migrate', ['--force' => true]);
        }

        $this->call('db:seed', [
            '--class' => CarpentrySeeder::class,
            '--force' => true,
        ]);

        $this->newLine();
        $this->info('Demo local lista.');
        $this->line('  URL:     '.rtrim((string) config('app.url'), '/').'/admin');
        $this->line('  Usuario: admin@gmail.com');
        $this->line('  Clave:   123456');
        $this->line('  Arranque: php artisan serve   (o composer run dev)');

        return self::SUCCESS;
    }
}
