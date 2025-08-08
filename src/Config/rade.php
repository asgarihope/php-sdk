<?php

return [
	'username' => env('RADE_USERNAME', ''), // Your mobile number is your username
	'password' => env('RADE_PASSWORD', ''), // Your Password :)
	'scopes'   => explode(',', env('RADE_SCOPES', '')),

	// Typically, changes should not be made and do not occur
	// unless in critical or exceptional situations, and even
	// then, only with direct notification and coordination
	// from the Rade Support team.
	'baseUrl'  => 'https://api.rade.ir/api',

];
