<?php

namespace App\Providers;

use App\Models\Contracts\Contract;
use App\Policies\Contracts\ContractPolicy;
use Illuminate\Support\ServiceProvider;
use App\Repositories\Contracts\ContractRepositoryInterface;
use App\Repositories\Contracts\EloquentContractRepository;

class AppServiceProvider extends ServiceProvider
{
    protected $policies = [
        Contract::class => ContractPolicy::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            ContractRepositoryInterface::class,
            EloquentContractRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
