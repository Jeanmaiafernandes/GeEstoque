@extends('layouts.app')

@section('title', 'Unidades')

@section('content')
    <div class="mb-4 flex items-center gap-2 text-sm text-slate-500">
        <a href="{{ route('settings.index') }}" class="hover:underline">Configurações</a>
        <span>/</span>
        <span>Unidades</span>
    </div>

    <h1 class="text-2xl font-semibold text-slate-800">Unidades de medida</h1>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2 overflow-x-auto rounded-lg bg-white shadow-sm">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Unidade</th>
                        <th class="px-4 py-3">Abreviação</th>
                        <th class="px-4 py-3 text-right">Materiais</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($units as $unit)
                        <tr>
                            <td class="px-4 py-3 font-medium text-slate-800">{{ $unit->name }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $unit->abbreviation ?: '—' }}</td>
                            <td class="px-4 py-3 text-right text-slate-500">{{ $unit->items_count }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-right">
                                <a href="{{ route('units.edit', $unit) }}" class="text-sm text-slate-600 hover:underline">Editar</a>
                                <form method="POST" action="{{ route('units.destroy', $unit) }}" class="inline"
                                      onsubmit="return confirm('Remover esta unidade?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="ml-3 text-sm text-red-600 hover:underline">Remover</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-6 text-center text-slate-500">Nenhuma unidade cadastrada.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="rounded-lg bg-white p-6 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-700">Nova unidade</h2>
            @if ($errors->any())
                <p class="mt-2 text-sm text-red-600">{{ $errors->first() }}</p>
            @endif
            <form method="POST" action="{{ route('units.store') }}" class="mt-3 space-y-3">
                @csrf
                <input type="text" name="name" value="{{ old('name') }}" placeholder="Nome (ex.: Quilograma)" required
                       class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                <input type="text" name="abbreviation" value="{{ old('abbreviation') }}" placeholder="Abreviação (ex.: KG)" maxlength="20"
                       class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                <button type="submit" class="w-full rounded bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">Adicionar</button>
            </form>
        </div>
    </div>
@endsection
