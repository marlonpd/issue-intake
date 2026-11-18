<?php

namespace App\Providers;

use App\Services\Summary\RuleBasedSummaryGenerator;
use App\Services\Summary\SummaryGenerator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SummaryGenerator::class, function ($app) {
            $driver = config('summary.driver');

            return match ($driver) {
                'rules' => $app->make(RuleBasedSummaryGenerator::class),
                default => throw new InvalidArgumentException("Unknown summary driver [{$driver}]."),
            };
        });
    }

    public function boot(): void
    {
        Model::preventLazyLoading(! $this->app->isProduction());
    }
}
