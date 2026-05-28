@extends('layouts.app')

@section('title', 'Conferir NF-e')

@section('content')
    <div class="mb-4 flex items-center gap-2 text-sm text-slate-500">
        <a href="{{ route('movements.index') }}" class="hover:underline">Movimentações</a>
        <span>/</span>
        <a href="{{ route('nfe.create') }}" class="hover:underline">Importar NF-e</a>
        <span>/</span>
        <span>Conferir</span>
    </div>

    <h1 class="text-2xl font-semibold text-slate-800">Conferir NF-e</h1>
    <p class="mt-1 text-slate-600">
        Nota <strong>{{ $dados['numero'] ?? '—' }}</strong>
        @if ($dados['fornecedor'])· Fornecedor <strong>{{ $dados['fornecedor'] }}</strong>@endif
        · {{ $produtos->count() }} {{ $produtos->count() === 1 ? 'produto lido' : 'produtos lidos' }}
    </p>
    @if ($chave)
        <p class="mt-1 font-mono text-xs text-slate-400">Chave: {{ $chave }}</p>
    @endif

    <form method="POST" action="{{ route('nfe.store') }}" class="mt-6">
        @csrf
        <input type="hidden" name="numero" value="{{ $dados['numero'] }}">
        <input type="hidden" name="fornecedor" value="{{ $dados['fornecedor'] }}">

        @if ($source === 'pdf')
            <div class="mb-3 rounded border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                Itens lidos de um <strong>PDF</strong> (extração aproximada). Confira, corrija e use "Adicionar item" para o que faltar.
            </div>
        @endif

        <p class="mb-3 rounded bg-teal-50 px-3 py-2 text-xs text-teal-800">
            Cada produto será <strong>cadastrado como material novo</strong> na categoria escolhida (se já existir pelo EAN, é reaproveitado).
            Defina a categoria, ajuste quantidade e preço; desmarque <strong>Incluir</strong> para pular um item.
        </p>

        <div class="overflow-x-auto rounded-lg bg-white shadow-sm">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Produto na NF-e</th>
                        <th class="px-4 py-3">Categoria</th>
                        <th class="px-4 py-3 text-right">Qtd.</th>
                        <th class="px-4 py-3 text-right">Preço (R$)</th>
                        <th class="px-4 py-3 text-center">Incluir</th>
                    </tr>
                </thead>
                <tbody id="nfe-linhas" class="divide-y divide-slate-100">
                    @forelse ($produtos as $i => $p)
                        @include('nfe._linha', ['i' => $i, 'p' => $p])
                    @empty
                        <tr id="nfe-vazio"><td colspan="5" class="px-4 py-6 text-center text-slate-500">Nenhum item lido. Use "Adicionar item".</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <button type="button" id="nfe-add" class="mt-3 rounded border border-slate-300 px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">+ Adicionar item</button>

        <div class="mt-6 flex gap-2">
            <button type="submit" class="rounded bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">Registrar entrada</button>
            <a href="{{ route('nfe.create') }}" class="rounded border border-slate-300 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Cancelar</a>
        </div>
    </form>

    <datalist id="nfe-categorias">
        @foreach ($categories as $category)
            <option value="{{ $category->name }}"></option>
        @endforeach
    </datalist>

    <template id="nfe-template">
        @include('nfe._linha', ['i' => '__INDEX__', 'p' => []])
    </template>

    <script>
        (function () {
            const tbody = document.getElementById('nfe-linhas');
            const template = document.getElementById('nfe-template');
            let index = {{ $produtos->count() }};

            document.getElementById('nfe-add').addEventListener('click', function () {
                document.getElementById('nfe-vazio')?.remove();
                tbody.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__INDEX__', index));
                index++;
            });
        })();
    </script>
@endsection
