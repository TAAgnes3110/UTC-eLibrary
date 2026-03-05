<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakeServiceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:service {name : The name of the service class}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new service class';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = Str::studly(trim($this->argument('name')));

        if (!Str::endsWith($name, 'Service')) {
            $name .= 'Service';
        }

        $path = app_path('Services/' . $name . '.php');

        if (file_exists($path)) {
            $this->error("Service [{$name}] already exists.");

            return self::FAILURE;
        }

        $stub = $this->getStub();

        $stub = str_replace('{{ namespace }}', 'App\\Services', $stub);
        $stub = str_replace('{{ class }}', $name, $stub);

        $directory = dirname($path);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($path, $stub);

        $this->info("Service [{$name}] created successfully.");

        return self::SUCCESS;
    }

    protected function getStub(): string
    {
        return <<<'STUB'
<?php

namespace {{ namespace }};

class {{ class }}
{
    //
}
STUB;
    }
}
