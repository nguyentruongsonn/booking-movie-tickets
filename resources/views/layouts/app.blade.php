<!DOCTYPE html>
<html lang="en">
<head>
	<title>NTS - Cinema</title>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<meta name="csrf-token" content="{{ csrf_token() }}">

	<link href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700,800,900" rel="stylesheet">
	<link href="https://fonts.googleapis.com/css2?family=Arizonia&display=swap" rel="stylesheet">

	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">

	<link rel="stylesheet" href=" {{ asset('css/animate.css') }}">
	<link rel="stylesheet" href="{{ asset('css/owl.carousel.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/owl.theme.default.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/magnific-popup.css') }}">

	<link rel="stylesheet" href="{{ asset('css/bootstrap-datepicker.css') }}">
	<link rel="stylesheet" href="{{ asset('css/jquery.timepicker.css') }}">
	@stack('styles')

	<link rel="stylesheet" href="{{ asset('css/flaticon.css') }}">
	<link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body>
    @include('partials.header')
    @yield('content')
    @include('partials.modal-auth')
    @include('partials.footer')
    @yield('scripts')
</body>
