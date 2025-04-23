<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Event Booking App') }}</title>

	@vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>
<body data-page="wrapper" class="h-100 bg-dark">

	<div class="container vh-100">
		<div class="row h-100 align-items-center justify-content-center">
			<div class="col text-light" style="max-width: 500px;">
				<h1 class="text-center">Event Booking System</h1>
				<p class="text-center">This is event booking and management application. To access it's features check out API documentations on swagger.</p>
				<div class="d-grid gap-2">
					<a class="btn btn-primary" href="{{ route('l5-swagger.default.api') }}" role="button">Swagger API Documentation &#8599;</a>
				</div>
			</div>
		</div>
	</div>

</body>
</html>
