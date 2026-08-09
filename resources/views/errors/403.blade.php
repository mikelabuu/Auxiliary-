{{--
    Says only that the door is closed, never whether there is a room behind it.
    "This booking belongs to another guest" would confirm the record exists and
    that the id was a real one — the same enumeration leak the login form is
    careful to avoid (see PRODUCT.md, "Reveal nothing on failure").
--}}
@extends('errors.layout')

@section('code', '403')
@section('title', 'Not available')
@section('heading', 'This page isn’t available to you.')
@section('message', 'You may be signed in to a different account than the one you meant to use. If you think this page should open for you, the front desk can check it.')
