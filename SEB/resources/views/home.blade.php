@extends('layouts.main')

@section('content')
<main class="home-page">
    <div class="home-layout">
        <aside class="home-sidebar">
            <div class="sidebar-brand">
                <p class="sidebar-brand-name">SEB Panel</p>
                <p class="sidebar-kicker">Supervisor Console</p>
            </div>
            
            <nav class="sidebar-nav">
                <a href="{{ route('home') }}" class="sidebar-link {{ ($page ?? 'dashboard') === 'dashboard' ? 'active' : '' }}">
                    <span class="link-icon">📊</span> Dashboard
                </a>
                
                <div class="sidebar-separator">Generate Codes</div>
                
                <a href="{{ route('code.login') }}" class="sidebar-link {{ ($page ?? '') === 'login' ? 'active' : '' }}" data-ajax-nav="code-page">
                    <span class="link-icon">🔑</span> Login Code
                </a>
                <a href="{{ route('code.unlock') }}" class="sidebar-link {{ ($page ?? '') === 'unlock' ? 'active' : '' }}" data-ajax-nav="code-page">
                    <span class="link-icon">🔓</span> Unlock Code
                </a>
                <a href="{{ route('code.exit') }}" class="sidebar-link {{ ($page ?? '') === 'exit' ? 'active' : '' }}" data-ajax-nav="code-page">
                    <span class="link-icon">🚪</span> Exit Code
                </a>
                
                <div class="sidebar-separator">System</div>
                
                <a href="{{ route('activity') }}" class="sidebar-link {{ ($page ?? '') === 'activity' ? 'active' : '' }}">
                    <span class="link-icon">📈</span> Activity Feed
                </a>
                <a href="{{ route('account') }}" class="sidebar-link {{ ($page ?? '') === 'account' ? 'active' : '' }}">
                    <span class="link-icon">⚙️</span> Account
                </a>
            </nav>

            <form method="POST" action="{{ route('logout') }}" class="sidebar-logout">
                @csrf
                <button type="submit" class="btn-sidebar-logout">Keluar Sesi</button>
            </form>
        </aside>

        <section class="main-content">
            @php
                $isCodePage = in_array(($page ?? ''), ['login', 'unlock', 'exit'], true);
            @endphp

            @if (session('status') && ! $isCodePage)
                <div class="home-alert success">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="home-alert error">{{ $errors->first() }}</div>
            @endif

            <section class="home-grid {{ ($page ?? 'dashboard') === 'dashboard' ? 'dashboard-only' : 'single-view' }}" id="dashboard">
                
                @if (($page ?? 'dashboard') === 'dashboard')
                    <article class="panel dashboard-hero">
                        <p class="panel-kicker">Ringkasan Sesi</p>
                        <h2 class="panel-title">Dashboard Ujian</h2>
                        <p class="panel-subtitle">Pantau kode akses aktif dan aktivitas siswa secara real-time dalam satu tampilan.</p>

                        <div class="dashboard-stats">
                            <div class="stat-card">
                                <p class="stat-label">Login Code</p>
                                <p class="stat-value">{{ $generatedCodes['enter']['code'] ?? '-' }}</p>
                                <p class="stat-meta">Generated: {{ $generatedCodes['enter']['generated_at'] ?? 'Belum ada' }}</p>
                            </div>
                            <div class="stat-card">
                                <p class="stat-label">Unlock Code</p>
                                <p class="stat-value">{{ $generatedCodes['unlock']['code'] ?? '-' }}</p>
                                <p class="stat-meta">Generated: {{ $generatedCodes['unlock']['generated_at'] ?? 'Belum ada' }}</p>
                            </div>
                            <div class="stat-card">
                                <p class="stat-label">Exit Code</p>
                                <p class="stat-value">{{ $generatedCodes['exit']['code'] ?? '-' }}</p>
                                <p class="stat-meta">Generated: {{ $generatedCodes['exit']['generated_at'] ?? 'Belum ada' }}</p>
                            </div>
                        </div>
                    </article>

                    <article class="panel">
                        <p class="panel-kicker">Log Sistem</p>
                        <h2 class="panel-title">Aktivitas Terbaru</h2>
                        
                        @if (empty($recentActivities))
                            <div class="history-empty">Belum ada aktivitas student terdeteksi.</div>
                        @else
                            <div class="activity-feed-list">
                                @foreach ($recentActivities as $activity)
                                    <div class="activity-feed-item">
                                        <div class="activity-indicator"></div>
                                        <div class="activity-content">
                                            <p class="activity-user">{{ $activity['student_name'] }} <span class="nis-tag">NIS: {{ $activity['student_nis'] }}</span></p>
                                            <p class="activity-action">{{ $activity['action'] }}</p>
                                            <p class="activity-time">{{ $activity['activity_at'] }} ({{ $timezoneLabel }})</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </article>
                @endif

                {{-- CONTENT UNTUK HALAMAN ACTIVITY --}}
                @if (($page ?? '') === 'activity')
                    <article class="panel">
                        <h2 class="panel-title">Pelanggaran Siswa</h2>
                        <p class="panel-subtitle">Menampilkan riwayat pelanggaran siswa selama sesi ujian.</p>

                        @if (empty($violationActivities))
                            <div class="history-empty">Belum ada pelanggaran.</div>
                        @else
                            <div class="activity-feed-list">
                                @foreach ($violationActivities as $violation)
                                    <div class="activity-feed-item">
                                        <div class="activity-indicator"></div>
                                        <div class="activity-content">
                                            <p class="activity-user">{{ $violation['student_name'] }} <span class="nis-tag">NIS: {{ $violation['student_nis'] }}</span></p>
                                            <p class="activity-action">{{ $violation['violation'] }}</p>
                                            <p class="activity-time">{{ $violation['detected_at'] }} ({{ $timezoneLabel }})</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </article>
                @endif

                {{-- CONTENT UNTUK HALAMAN GENERATE CODES (LOGIN/UNLOCK/EXIT) --}}
                @if (in_array($page, ['login', 'unlock', 'exit']))
                    <article class="panel">
                        <h2 class="panel-title">Generate {{ ucfirst($page) }} Code</h2>
                        <p class="panel-subtitle">Buat kode akses baru untuk dibagikan kepada siswa.</p>

                        <form method="POST" action="{{ route('code.generate') }}" class="login-form">
                            @csrf
                            <input type="hidden" name="code_type" value="{{ $page === 'login' ? 'enter' : $page }}">
                            <input type="hidden" name="timezone_offset_minutes" value="-420">

                            <button type="submit" class="btn-login">Generate Kode Sekarang</button>
                        </form>

                        <div class="stat-card" style="margin-top: 32px; border: 1px solid var(--line);">
                            <p class="stat-label">KODE AKTIF SAAT INI</p>
                            <div style="display:flex; align-items:center; gap:16px;">
                                <p class="stat-value" id="{{ $page }}-active-code">{{ $generatedCodes[$page === 'login' ? 'enter' : $page]['code'] ?? '-' }}</p>
                                <button type="button" class="sidebar-link" id="copy-{{ $page }}-code-btn" style="padding: 8px;">
                                    <span id="copy-{{ $page }}-code-icon">🔗 Copy</span>
                                </button>
                            </div>
                        </div>
                    </article>
                @endif

                {{-- HALAMAN ACCOUNT --}}
                @if ($page === 'account')
                    <article class="panel">
                        <h2 class="panel-title">Pengaturan Profil</h2>
                        <p class="panel-subtitle">Perbarui data profil supervisor.</p>

                        <form method="POST" action="{{ route('account.update') }}" class="login-form">
                            @csrf
                            <div class="field-group">
                                <label>NAMA</label>
                                <input name="name" type="text" value="{{ $supervisorProfile['name'] ?? '' }}" placeholder="Nama supervisor">
                            </div>
                            <div class="field-group">
                                <label>USERNAME</label>
                                <input type="text" value="{{ $supervisorProfile['username'] ?? '' }}" readonly disabled>
                            </div>
                            <div class="field-group">
                                <label>EMAIL</label>
                                <input name="email" type="email" value="{{ $supervisorProfile['email'] ?? '' }}">
                            </div>
                            <button type="submit" class="btn-login">Simpan Perubahan</button>
                        </form>
                    </article>

                    <article class="panel">
                        <h2 class="panel-title">Ganti Password</h2>
                        <p class="panel-subtitle">Masukkan password lama terlebih dahulu sebelum mengganti password.</p>

                        <form method="POST" action="{{ route('account.password.update') }}" class="login-form">
                            @csrf
                            <div class="field-group">
                                <label>PASSWORD LAMA</label>
                                <input name="current_password" type="password" required>
                            </div>
                            <div class="field-group">
                                <label>PASSWORD BARU</label>
                                <input name="password" type="password" required minlength="6">
                            </div>
                            <div class="field-group">
                                <label>KONFIRMASI PASSWORD BARU</label>
                                <input name="password_confirmation" type="password" required minlength="6">
                            </div>
                            <button type="submit" class="btn-login">Update Password</button>
                        </form>
                    </article>
                @endif

            </section>
        </section>
    </div>
</main>

@if (session('status') && $isCodePage)
<div class="status-modal-backdrop" id="status-modal-backdrop">
    <div class="login-card" style="max-width: 360px; text-align: center;">
        <h3 class="login-title" style="font-size: 20px;">Berhasil</h3>
        <p class="panel-subtitle" style="margin-bottom: 24px;">{{ session('status') }}</p>
        <button type="button" class="btn-login" id="status-modal-close" style="width: 100%;">OK</button>
    </div>
</div>
@endif

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Logic untuk Copy Code, Tab Activity, dan Timezone tetap sama
    // dan target ID elemen sesuai dengan HTML di atas.

    var statusModal = document.getElementById('status-modal-backdrop');
    if (statusModal) {
        document.getElementById('status-modal-close').addEventListener('click', function() {
            statusModal.style.display = 'none';
        });
    }

    function fallbackCopyText(text) {
        var textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.setAttribute('readonly', '');
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.select();
        textarea.setSelectionRange(0, 99999);
        var success = false;

        try {
            success = document.execCommand('copy');
        } catch (e) {
            success = false;
        }

        document.body.removeChild(textarea);
        return success;
    }

    function copyText(text) {
        if (!text) {
            return Promise.resolve(false);
        }

        if (navigator.clipboard && window.isSecureContext) {
            return navigator.clipboard.writeText(text)
                .then(function () { return true; })
                .catch(function () { return fallbackCopyText(text); });
        }

        return Promise.resolve(fallbackCopyText(text));
    }

    function wireCopyButton(page) {
        var button = document.getElementById('copy-' + page + '-code-btn');
        var codeEl = document.getElementById(page + '-active-code');
        var iconEl = document.getElementById('copy-' + page + '-code-icon');

        if (!button || !codeEl || !iconEl) {
            return;
        }

        button.addEventListener('click', function () {
            var text = (codeEl.textContent || '').trim();
            if (!text || text === '-') {
                iconEl.textContent = 'Tidak ada kode';
                setTimeout(function () { iconEl.textContent = '🔗 Copy'; }, 1500);
                return;
            }

            copyText(text).then(function (ok) {
                iconEl.textContent = ok ? '✔ Copied' : 'Gagal copy';
                setTimeout(function () { iconEl.textContent = '🔗 Copy'; }, 1500);
            });
        });
    }

    ['login', 'unlock', 'exit'].forEach(wireCopyButton);

    // Timezone website dikunci ke UTC+07:00 (WIB)
});
</script>
@endsection
