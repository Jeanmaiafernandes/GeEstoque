@extends('layouts.app')

@section('title', 'Categorias')

@section('content')
    <h1 class="text-2xl font-semibold text-slate-800">Categorias</h1>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2 overflow-x-auto rounded-lg bg-white shadow-sm">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Categoria</th>
                        <th class="px-4 py-3">Setor</th>
                        <th class="px-4 py-3">Tipo</th>
                        <th class="px-4 py-3 text-right">Materiais</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($categories as $category)
                        <tr>
                            <td class="px-4 py-3 font-medium text-slate-800">{{ $category->name }}</td>
                            <td class="px-4 py-3">
                                @if ($category->sector)
                                    <span class="text-slate-600">{{ $category->sector->name }}</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600">Global</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if ($category->is_weight_category)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-teal-50 px-2.5 py-0.5 text-xs font-medium text-teal-700">
                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z"/></svg>
                                        Por peso (kg)
                                    </span>
                                @else
                                    <span class="text-xs text-slate-400">Unitária</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right text-slate-500">{{ $category->items_count }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-right">
                                <a href="{{ route('categories.edit', $category) }}" class="text-sm text-slate-600 hover:underline">Editar</a>
                                <form method="POST" action="{{ route('categories.destroy', $category) }}" class="inline"
                                      onsubmit="return confirm('Remover esta categoria? Os materiais ficarão sem categoria.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="ml-3 text-sm text-red-600 hover:underline">Remover</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-6 text-center text-slate-500">Nenhuma categoria cadastrada.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="rounded-lg bg-white p-6 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-700">Nova categoria</h2>
            @if ($errors->any())
                <p class="mt-2 text-sm text-red-600">{{ $errors->first() }}</p>
            @endif
            <form method="POST" action="{{ route('categories.store') }}" class="mt-3 space-y-3">
                @csrf
                <input type="text" name="name" value="{{ old('name') }}" placeholder="Nome da categoria" required
                       class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                <div>
                    <label for="sector_id" class="block text-xs font-medium text-slate-600">Setor</label>
                    <select id="sector_id" name="sector_id" class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm">
                        <option value="">Global (todos os setores)</option>
                        @foreach ($sectors as $sector)
                            <option value="{{ $sector->id }}" @selected(old('sector_id') == $sector->id)>{{ $sector->name }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-slate-400">Global = visível em todos os setores.</p>
                </div>
                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="is_weight_category" value="1"
                           class="rounded border-slate-300"
                           @checked(old('is_weight_category', false))>
                    Categoria por peso (kg)
                    <span class="text-xs text-slate-400">— ex.: Chapas, Tubos</span>
                </label>
                <button type="submit" class="w-full rounded bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">Adicionar</button>
            </form>
        </div>
    </div>
@endsection
