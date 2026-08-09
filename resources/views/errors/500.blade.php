{{--
    Deliberately says nothing about what broke. The message is scoped to the one
    question the person actually has — "did my booking go through?" — and
    answers it honestly: we don't know from here, so check rather than assume.
--}}
@extends('errors.layout')

@section('code', '500')
@section('title', 'Something went wrong')
@section('heading', 'Something went wrong on our side.')
@section('message', 'This one is ours, not yours. If you were in the middle of booking or paying, check My Bookings before trying again so you don’t send it twice.')
