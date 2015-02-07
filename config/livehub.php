<?php

return [

	/**
	 * Branding
	 *
	 * Title to use for the site
	 */
	'brand' => 'LiveHub',

	/**
	 * Checker Driver
	 *
	 * Checker to use for checking external services
	 *
	 * Options: "none", "cron"
	 */
	'checker' => env('LIVEHUB_CHECKER', 'none'),
];
