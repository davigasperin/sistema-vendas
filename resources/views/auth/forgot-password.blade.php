@extends('layouts.app')

@section('title', 'Recuperar Senha')

@section('content')
<div class="container">
    <div class="row justify-content-center mt-5">
        <div class="col-md-5">
            <div class="card">
                <div class="card-header">Recuperar Senha</div>
                <div class="card-body">
                    <p class="text-muted mb-3">Informe seu email para receber o link de recuperação.</p>

                    @if(session('status'))
                        <div class="alert alert-success">{{ session('status') }}</div>
                    @endif

                    <form method="POST" action="{{ route('password.email') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" id="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <a href="{{ route('login') }}">Voltar ao login</a>
                            <button type="submit" class="btn btn-primary">Enviar Link</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection