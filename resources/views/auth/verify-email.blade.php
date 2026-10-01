@extends('layouts.app')

@section('title', 'Verificar Email')

@section('content')
<div class="container">
    <div class="row justify-content-center mt-5">
        <div class="col-md-5">
            <div class="card">
                <div class="card-header">Verificar Email</div>
                <div class="card-body">
                    <p class="text-muted mb-3">
                        Obrigado por se registrar! Antes de começar, verifique seu email clicando no link que enviamos.
                    </p>

                    @if(session('status') == 'verification-link-sent')
                        <div class="alert alert-success">Um novo link de verificação foi enviado para seu email.</div>
                    @endif

                    <div class="d-flex justify-content-between align-items-center">
                        <form method="POST" action="{{ route('verification.send') }}">
                            @csrf
                            <button type="submit" class="btn btn-link">Reenviar Email</button>
                        </form>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-secondary">Sair</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection