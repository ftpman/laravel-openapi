<?php

namespace Vyuldashev\LaravelOpenApi\Builders\Paths\Operation;

use GoldSpecDigital\ObjectOrientedOAS\Objects\Parameter;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Illuminate\Support\Collection;
use ReflectionParameter;
use ReflectionType;
use Vyuldashev\LaravelOpenApi\Annotations\Parameters;
use Vyuldashev\LaravelOpenApi\Factories\ParametersFactory;
use Vyuldashev\LaravelOpenApi\RouteInformation;
use Vyuldashev\LaravelOpenApi\SchemaHelpers;

class ParametersBuilder
{
    public function build(RouteInformation $route): array
    {
        $pathParameters = $this->buildPath($route);
        $annotatedParameters = $this->buildAnnotation($route);

        return $pathParameters->merge($annotatedParameters)->toArray();
    }

    protected function buildPath(RouteInformation $route): Collection
    {
        return collect($route->parameters)
            ->map(static function (array $parameter) use ($route) {
                $schema = Schema::string();

                /** @var ReflectionType|null $reflectionParameter */
                $reflectionParameter = collect($route->actionParameters)
                    ->first(static function (ReflectionParameter $reflectionParameter) use ($parameter) {
                        return $reflectionParameter->name === $parameter['name'];
                    });

                if ($reflectionParameter) {
                    $schema = SchemaHelpers::guessFromReflectionType($reflectionParameter->getType());
                }

                return Parameter::path()->name($parameter['name'])
                    ->required()
                    ->schema($schema);
            });
    }

    protected function buildAnnotation(RouteInformation $route): Collection
    {
        /** @var Parameters|null $parameters */
        $parameters = collect($route->actionAnnotations)->first(static function ($annotation) {
            return $annotation instanceof Parameters;
        }, []);

        if ($parameters) {
            /** @var ParametersFactory $parametersFactory */
            $parametersFactory = resolve($parameters->factory);

            $parameters = $parametersFactory->build();
        }

        return collect($parameters);
    }
}
