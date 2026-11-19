@extends('errors.layout')

@php
    $status = isset($exception) && method_exists($exception, 'getStatusCode') ? $exception->getStatusCode() : 400;
    $detail = trim((string) (isset($exception) ? $exception->getMessage() : ''));
    $showDetail = $detail !== '' && $status === 422;
@endphp

@section('code', (string) $status)
@section('title', 'Permintaan Tidak Dapat Diproses')
@section('message', $showDetail
    ? $detail
    : 'Permintaan Anda tidak dapat diproses. Periksa kembali data yang dikirim lalu coba lagi.')
