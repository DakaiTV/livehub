<?php namespace t2t2\LiveHub\Providers;

use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{

	/**
	 * The event handler mappings for the application.
	 *
	 * @var array
	 */
	protected $listen = [
		't2t2\LiveHub\Events\SomeEvent' => [
			't2t2\LiveHub\Listeners\EventListener',
		],
	];

	/**
	 * Register any other events for your application.
	 *
	 * @param DispatcherContract $events
	 */
	public function boot(DispatcherContract $events)
    {
		parent::boot($events);

		//
	}
}
