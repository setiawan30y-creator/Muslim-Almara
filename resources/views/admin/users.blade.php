{{-- Admin: pengguna & peran — @2026-Alifiandra --}}
<x-layouts.admin title="Pengguna">
    <x-page-head title="Pengguna & peran" subtitle="Peran editor, reviewer, dan admin dipisahkan" />
    <form method="get" class="toolbar"><div class="input-wrap grow"><x-icon name="search" /><label for="q" class="sr-only">Cari</label><input id="q" name="q" class="input" value="{{ $q }}" placeholder="Cari nama atau email…"></div></form>
    <div class="table-wrap">
        <table class="ledger ledger--cards">
            <thead><tr><th>Nama</th><th>Email</th><th>Terdaftar</th><th>Peran</th></tr></thead>
            <tbody>
                @foreach ($users as $usr)
                    <tr>
                        <td data-label="Nama"><b>{{ $usr->name }}</b></td>
                        <td data-label="Email">{{ $usr->email }} @if (! $usr->email_verified_at)<span class="badge badge--neutral">belum verifikasi</span>@endif</td>
                        <td data-label="Terdaftar">{{ $usr->created_at?->translatedFormat('j M Y') }}</td>
                        <td data-label="Peran">
                            <form method="post" action="{{ route('admin.users.roles', $usr) }}" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
                                @csrf @method('put')
                                @foreach ($roles as $r)
                                    <label class="check" style="font-size:.85rem"><input type="checkbox" name="roles[]" value="{{ $r->id }}" @checked($usr->roles->contains('id', $r->id))> {{ $r->label }}</label>
                                @endforeach
                                <button class="btn btn--outline btn--sm">Simpan</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    {{ $users->links('partials.pagination') }}
</x-layouts.admin>
