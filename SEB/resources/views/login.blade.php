@extends('layouts.main')

@section('content')
<main class="login-page emerald-slate-theme">
    <div class="deco-circle circle-top" aria-hidden="true"></div>
    <div class="deco-circle circle-bottom" aria-hidden="true"></div>

    <section class="login-stage">
        <section class="login-card">
            <header class="login-header">
                <span class="login-kicker">Selamat Datang</span>
                <h2 class="login-title">Portal Ujian</h2>
            </header>

            @if ($errors->any())
                <div class="login-alert">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('login.attempt') }}" class="login-form">
                @csrf

                <div class="field-group">
                    <label for="username">USERNAME</label>
                    <input id="username" name="username" type="text" value="{{ old('username') }}" placeholder="Masukkan username" required autofocus>
                </div>

                <div class="field-group">
                    <label for="password">PASSWORD</label>
                    <input id="password" name="password" type="password" placeholder="••••••••" required>
                </div>

                <button type="submit" class="btn-login">Masuk Sekarang</button>
            </form>

            <footer class="login-footer-deco">
                <span class="android-compass-icon"></span>
            </footer>
        </section>
    </section>
</main>
@endsection