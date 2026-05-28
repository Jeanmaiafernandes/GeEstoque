@extends('layouts.app')

@section('title', 'Configurações — Dados da empresa')

@section('content')
    <div class="mb-4 flex items-center gap-2 text-sm text-slate-500">
        <a href="{{ route('settings.index') }}" class="hover:underline">Configurações</a>
        <span>/</span>
        <span>Dados da empresa</span>
    </div>

    <h1 class="text-2xl font-semibold text-slate-800">Dados da empresa</h1>
    <p class="mt-1 text-sm text-slate-500">Aparecem no cabeçalho dos relatórios em PDF e no login/menu (logo).</p>

    @if ($errors->any())
        <div class="mt-4 max-w-2xl rounded border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-700">
            <ul class="list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('settings.company.update') }}" enctype="multipart/form-data" class="mt-6 max-w-2xl rounded-lg bg-white p-6 shadow-sm">
        @csrf
        @method('PUT')

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="company_name" class="block text-sm font-medium text-slate-700">Nome da empresa</label>
                <input id="company_name" name="company_name" type="text" maxlength="150" value="{{ old('company_name', $name) }}"
                       class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label for="company_cnpj" class="block text-sm font-medium text-slate-700">CNPJ</label>
                <input id="company_cnpj" name="company_cnpj" type="text" maxlength="20" value="{{ old('company_cnpj', $cnpj) }}"
                       class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label for="company_phone" class="block text-sm font-medium text-slate-700">Telefone</label>
                <input id="company_phone" name="company_phone" type="text" maxlength="30" value="{{ old('company_phone', $phone) }}"
                       class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div class="sm:col-span-2">
                <label for="company_email" class="block text-sm font-medium text-slate-700">E-mail</label>
                <input id="company_email" name="company_email" type="email" maxlength="150" value="{{ old('company_email', $email) }}"
                       class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm">
            </div>
        </div>

        <div class="mt-6 border-t border-slate-100 pt-4">
            <p class="text-sm font-semibold text-slate-700">Logo</p>
            <p class="text-xs text-slate-400">PNG ou JPG, até 2 MB. Substitui o monograma "Ge" no login e no menu.</p>

            @if ($logoUrl)
                <div class="mt-3 flex items-center gap-4">
                    <img src="{{ $logoUrl }}" alt="Logo atual" class="h-14 w-14 rounded-lg border border-slate-200 object-contain p-1">
                    <label class="flex items-center gap-2 text-sm text-slate-600">
                        <input type="checkbox" name="remove_logo" value="1" class="rounded border-slate-300 text-red-600">
                        Remover logo atual
                    </label>
                </div>
            @endif

            <input id="logo" name="logo" type="file" accept="image/png,image/jpeg"
                   class="mt-3 block w-full text-sm text-slate-600 file:mr-3 file:rounded file:border-0 file:bg-teal-700 file:px-4 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-teal-800">
        </div>

        <div class="mt-6">
            <button type="submit" class="rounded bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">Salvar</button>
        </div>
    </form>
@endsection
