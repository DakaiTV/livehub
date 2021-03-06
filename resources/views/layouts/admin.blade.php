<?php
if (! isset($javascript)) {
	$javascript = [];
}
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>{{ (isset($title) ? $title . ' | ' : '') . config('livehub.brand') }}</title>

	<link rel="stylesheet" href="{{ asset(versioned('/assets/admin.css')) }}"/>
</head>
<body class="no-js" data-config="{{ json_encode($javascript) }}">
	@include('partials.admin.navbar')

	@if (session('status'))
		<div class="callout" data-closable>
			{{ session('status') }}
			<button class="close-button" aria-label="Dismiss alert" type="button" data-close>
				<span aria-hidden="true">&times;</span>
			</button>
		</div>
	@endif

	@if(count($errors))
		<div class="callout alert">
			<strong>Whoaaaaa</strong> Something's not quite right
			<ul>
				@foreach($errors->all() as $error)
					<li>{{ $error }}</li>
				@endforeach
			</ul>
		</div>
	@endif

	@yield('content')

	<script src="{{ asset(versioned('/assets/admin.js')) }}"></script>
</body>
</html>