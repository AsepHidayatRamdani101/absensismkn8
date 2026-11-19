@extends('errors.layout')

@php
    $detail = trim((string) (isset($exception) ? $exception->getMessage() : ''));
    $generic = ['', 'Forbidden', 'This action is unauthorized.', 'User does not have the right roles.', 'User does not have the right permissions.', 'Akses ditolak.'];
@endphp

@section('code', '403')
@section('title', 'Akses Ditolak')
@section('message', in_array($detail, $generic, true)
    ? 'Anda tidak memiliki izin untuk membuka halaman ini. Hubungi admin sekolah bila menurut Anda ini keliru.'
    : $detail)
