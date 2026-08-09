{{--
    The one people actually hit. A form left open too long — most often the
    checkout — posts with a stale CSRF token and Laravel answers 419, which by
    default renders as the words "Page Expired" on a white page. That reads as a
    fault in the site rather than a timeout, and it lands mid-booking.

    The lead action goes to sign-in rather than "back": the token is stale
    because the session is, so returning to the form would just fail again.
--}}
@extends('errors.layout')

@section('code', '419')
@section('title', 'Session expired')
@section('lead_action_label', 'Sign in again')
@section('lead_action_href', route('login'))
@section('heading', 'Your session timed out.')
@section('message', 'You were away long enough that we closed the session for safety, so the page you just submitted is no longer signed in. Sign in again and your details will still be there.')
