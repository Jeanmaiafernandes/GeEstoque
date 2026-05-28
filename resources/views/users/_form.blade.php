@php($user = $user ?? null)

@if ($errors->any())
    <div class="mb-4 rounded border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-700">
        <ul class="list-inside list-disc">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="space-y-4">
    <div>
        <label for="name" class="block text-sm font-medium text-slate-700">Nome *</label>
        <input id="name" name="name" type="text" value="{{ old('name', $user?->name) }}" required
               class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm">
    </div>
    <div>
        <label for="email" class="block text-sm font-medium text-slate-700">E-mail *</label>
        <input id="email" name="email" type="email" value="{{ old('email', $user?->email) }}" required
               class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm">
    </div>
    <div>
        <label for="password" class="block text-sm font-medium text-slate-700">
            Senha {{ $user ? '' : '*' }}
        </label>
        <input id="password" name="password" type="password" {{ $user ? '' : 'required' }} autocomplete="new-password"
               class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm">
        @if ($user)
            <p class="mt-1 text-xs text-slate-400">Deixe em branco para manter a senha atual.</p>
        @else
            <p class="mt-1 text-xs text-slate-400">Mínimo de 8 caracteres.</p>
        @endif
    </div>
    <div>
        <label for="role" class="block text-sm font-medium text-slate-700">Perfil *</label>
        <select id="role" name="role" required class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm">
            @foreach ($roles as $value => $label)
                <option value="{{ $value }}" @selected(old('role', $user?->role?->value) === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
</div>
