<?php

declare(strict_types=1);

namespace Vyuldashev\LaravelOpenApi;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Vyuldashev\LaravelOpenApi\Builders\Components\ResponsesBuilder;
use Vyuldashev\LaravelOpenApi\Builders\Components\SchemasBuilder;

class OpenApiServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/openapi.php' => config_path('openapi.php'),
            ], 'openapi-config');
        }

        $this->registerAnnotations();

        ResponsesBuilder::in($this->responsesIn());
        SchemasBuilder::in($this->schemasIn());

        $this->app->singleton(Generator::class, static function ($app) {
            $config = config('openapi');

            return new Generator($app, $config);
        });
    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/openapi.php', 'openapi'
        );

        $this->commands([
            Console\GenerateCommand::class,
            Console\ParametersFactoryMakeCommand::class,
            Console\RequestBodyFactoryMakeCommand::class,
            Console\ResponseFactoryMakeCommand::class,
            Console\SchemaFactoryMakeCommand::class,
        ]);
    }

    public function provides(): array
    {
        return [
            Generator::class,
        ];
    }

    protected function registerAnnotations(): void
    {
        $files = glob(__DIR__ . '/Annotations/*.php');

        foreach ($files as $file) {
            AnnotationRegistry::registerFile($file);
        }
    }

    protected function responsesIn(): array
    {
        return [
            app_path('OpenApi/Responses'),
        ];
    }

    protected function schemasIn(): array
    {
        return [
            app_path('OpenApi/Schemas'),
        ];
    }
}
