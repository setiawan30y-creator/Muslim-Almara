{{-- Verifikasi email — @2026-Alifiandra --}}
<x-layouts.app title="Verifikasi email" :noindex="true">
    <div style="max-width:520px;margin:0 auto" class="card card--pad-lg">
        <h1>Verifikasi email</h1>
        <p class="muted">Kami telah mengirim tautan verifikasi ke <b>{{ auth()->user()->email }}</b>. Buka email tersebut untuk menyelesaikan verifikasi.</p>
        <form method="post" action="{{ route('verification.send') }}">@csrf<button class="btn btn--tonal" data-loading>Kirim ulang tautan</button></form>
    </div>
</x-layouts.app>
