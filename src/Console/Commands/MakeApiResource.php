<?php

namespace Specialtactics\L5Api\Console\Commands;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

class MakeApiResource extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:api-resource {name : The name of the API resource (eg. User)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '轻松创建API资源的基础架构';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->setupStyles();

        $name = ucfirst($this->argument('name'));

        //
        // The basics - Model - Controller - (Policy)
        //

        $this->call('make:model', ['name' => $name]);

        $this->call('make:controller', ['name' => $name.'Controller', '--model' => $name]);

        // Conditionally create policy
        if ($this->anticipate('是否对于此资源创建授权策略?', ['y', 'n']) == 'y') {
//            $policyName = '../Models/Policies/' . $name . 'Policy';
            $this->call('make:policy', ['name' => $name . 'Policy', '-m' => $name]);
        }

        //
        // Database related generation
        //

        // Create a migration
        $migrationName = Str::snake(Str::pluralStudly($name));
        $this->call('make:migration', ['name' => "create_{$migrationName}_table"]);

        // Conditionally create seeder
        if ($this->anticipate('是否对于此资源创建数据填充?', ['y', 'n']) == 'y') {
            $seederName = Str::plural($name) . 'Seeder';

            $this->call('make:seeder', ['name' => $seederName]);

            $this->line('请在DatabaseSeeder.php文件中添加引用：', 'important');
            $this->line('$this->call('. $seederName .'::class);', 'code');
            $this->line(PHP_EOL);
        }

        //
        // Spit out example routes
        //

        $this->line('如果需要添加路由，请复制如下代码到routes/api.php文件指定位置', 'important');

        $sectionName = Str::pluralStudly($name);
        $routePrefix = Str::plural(Str::kebab($name));
        $controllerName = $name . 'Controller';

        $exampleRoutes =
            '/*' . PHP_EOL .
            ' * ' . $sectionName . PHP_EOL .
            ' */' . PHP_EOL .
            '$api->group([\'prefix\' => \''. $routePrefix .'\'], function ($api) {' . PHP_EOL .
            '    $api->get(\'/\', \'App\Http\Controllers\\'. $controllerName .'@getAll\');' . PHP_EOL .
            '    $api->get(\'/{id}\', \'App\Http\Controllers\\'. $controllerName .'@get\');' . PHP_EOL .
            '    $api->post(\'/\', \'App\Http\Controllers\\'. $controllerName .'@post\');' . PHP_EOL .
            '    $api->patch(\'/{id}\', \'App\Http\Controllers\\'. $controllerName .'@patch\');' . PHP_EOL .
            '    $api->delete(\'/{id}\', \'App\Http\Controllers\\'. $controllerName .'@delete\');' . PHP_EOL .
            '});';

        $this->line($exampleRoutes, 'code');
    }

    /**
     * Setup styles for command
     */
    protected function setupStyles()
    {
        $style = new OutputFormatterStyle('yellow', 'black', ['bold']);
        $this->output->getFormatter()->setStyle('important', $style);

        $style = new OutputFormatterStyle('cyan', 'black', ['bold']);
        $this->output->getFormatter()->setStyle('code', $style);
    }
}
