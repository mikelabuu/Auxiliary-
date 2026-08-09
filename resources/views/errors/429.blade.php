{{--
    Says "wait a little", never how little.

    Every throttle in this app has a published-looking number behind it — 5
    attempts per email, a 15-minute decay, 20 per IP per minute — and printing
    any of them here would hand an attacker the exact shape of the limiter:
    how far to push, and how long to sleep between bursts. PRODUCT.md makes this
    binding ("attempt limits, lockout windows, resend caps... stay out of the
    UI"). Vagueness costs a legitimate user nothing; they only need to know that
    waiting is the fix.
--}}
@extends('errors.layout')

@section('code', '429')
@section('title', 'Too many requests')
@section('heading', 'That was a few too many tries.')
@section('message', 'We’ve paused requests from here for a little while. Wait a bit before trying again — repeating it now will only extend the wait.')
