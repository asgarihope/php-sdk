<?php

namespace Radeir\Provider;

use Illuminate\Support\ServiceProvider;
use Radeir\Services\RadeServices;
use Radeir\Services\TokenManager\DefaultTokenManager;
use Radeir\Services\TokenManager\TokenManagerInterface;

class RadeServiceProvider extends ServiceProvider
{
	public function register(): void
	{
		$this->mergeConfigFrom(__DIR__ . '/../Config/rade.php', 'rade');

		$this->app->singleton(TokenManagerInterface::class, function (array $app): DefaultTokenManager {
			$config = $app['config']['rade'];
			return new DefaultTokenManager($config);
		});

		$this->app->singleton(RadeServices::class, function (array $app): RadeServices {
			$config = $app['config']['rade'];
			$tokenManager = $app->make(TokenManagerInterface::class);
			return new RadeServices($config, $tokenManager);
		});
	}

	public function boot(): void
	{
		$this->publishes([
			__DIR__ . '/../Config/rade.php' => config_path('rade.php'),
		], 'config-rade');
	}
}
