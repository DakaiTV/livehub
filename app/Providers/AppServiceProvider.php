<?php namespace t2t2\LiveHub\Providers;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{

	/**
	 * Bootstrap any application services.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->customValidator();
	}

	/**
	 * Register any application services.
	 *
	 * @return void
	 */
	public function register()
	{
		// Dev
		if ($this->app->environment('local')) {
			$this->registerIDEHelper();
		}
	}

	/**
	 * Implement custom validator with extra rules
	 */
	public function customValidator()
	{
		\Validator::extend('date_parseable', 't2t2\LiveHub\Custom\ValidatorRules@parseableDate');
		\Validator::extend('valid_regex', 't2t2\LiveHub\Custom\ValidatorRules@validRegex');
		\Validator::extend('time', 't2t2\LiveHub\Custom\ValidatorRules@time');
	}

	/**
	 * Register IDE Helper
	 */
	protected function registerIDEHelper()
	{
		$this->app->register('Barryvdh\\LaravelIdeHelper\\IdeHelperServiceProvider');
	}
}
