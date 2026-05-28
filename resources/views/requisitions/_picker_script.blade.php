@php
    $itensJson = $items->map(fn ($i) => [
        'id'       => $i->id,
        'label'    => $i->code . ' — ' . $i->name,
        'stock'    => $i->current_stock_label,
        'unit'     => $i->unit->abbreviation,
        'category' => (string) $i->category_id,
        'sector'   => (string) $i->sector_id,
    ])->values();
@endphp
<script>
(function () {
    const container = document.getElementById('linhas');
    const template  = document.getElementById('linha-template');
    const filtroCat = document.getElementById('filtro-categoria');
    const filtroSet = document.getElementById('filtro-setor');
    let index = {{ count($oldLines) }};

    const ITENS = @json($itensJson);

    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }

    // Mostra no filtro de categoria apenas as do setor escolhido (+ globais).
    function filtrarCategorias() {
        const set = filtroSet.value;
        Array.from(filtroCat.options).forEach(function (o) {
            if (!o.value) { return; }
            const oSetor = o.dataset.sector || '';
            o.hidden = !(!set || oSetor === '' || oSetor === set);
        });
        if (filtroCat.selectedOptions[0] && filtroCat.selectedOptions[0].hidden) {
            filtroCat.value = '';
        }
    }

    // Materiais candidatos: respeita setor + categoria + texto digitado.
    function candidatos(q) {
        const set = filtroSet.value;
        const cat = filtroCat.value;
        q = (q || '').trim().toLowerCase();
        return ITENS.filter(function (it) {
            if (set && it.sector !== set) { return false; }
            if (cat && it.category !== cat) { return false; }
            if (q && !it.label.toLowerCase().includes(q)) { return false; }
            return true;
        }).slice(0, 50);
    }

    function renderLista(ul, q) {
        const matches = candidatos(q);
        ul.innerHTML = matches.length
            ? matches.map(it =>
                `<li class="opcao cursor-pointer px-3 py-1.5 text-sm hover:bg-teal-50" data-id="${it.id}" data-label="${escapeHtml(it.label)}" data-stock="${escapeHtml(it.stock)}" data-unit="${escapeHtml(it.unit)}">${escapeHtml(it.label)}</li>`
              ).join('')
            : '<li class="px-3 py-1.5 text-sm text-slate-400">Nenhum material encontrado.</li>';
        ul.classList.remove('hidden');
    }

    document.getElementById('add-linha').addEventListener('click', function () {
        container.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__INDEX__', index));
        index++;
    });

    container.addEventListener('click', function (e) {
        if (e.target.classList.contains('remover-linha') && container.querySelectorAll('.linha').length > 1) {
            e.target.closest('.linha').remove();
        }
    });

    // Digitar → filtra e mostra a lista; zera o item escolhido até clicar numa opção.
    container.addEventListener('input', function (e) {
        if (!e.target.classList.contains('linha-busca')) { return; }
        const linha = e.target.closest('.linha');
        linha.querySelector('.linha-item-id').value = '';
        linha.querySelector('.linha-saldo').textContent = '';
        renderLista(linha.querySelector('.linha-opcoes'), e.target.value);
    });

    // Focar no campo → abre a lista de candidatos.
    container.addEventListener('focusin', function (e) {
        if (!e.target.classList.contains('linha-busca')) { return; }
        const linha = e.target.closest('.linha');
        renderLista(linha.querySelector('.linha-opcoes'), e.target.value);
    });

    // Sair do campo → fecha a lista (com atraso para permitir o clique).
    container.addEventListener('focusout', function (e) {
        if (!e.target.classList.contains('linha-busca')) { return; }
        const ul = e.target.closest('.linha').querySelector('.linha-opcoes');
        setTimeout(() => ul.classList.add('hidden'), 150);
    });

    // Escolher uma opção (mousedown ocorre antes do blur do input).
    container.addEventListener('mousedown', function (e) {
        const li = e.target.closest('.opcao');
        if (!li) { return; }
        e.preventDefault();
        const linha = li.closest('.linha');
        linha.querySelector('.linha-busca').value = li.dataset.label;
        linha.querySelector('.linha-item-id').value = li.dataset.id;
        linha.querySelector('.linha-saldo').textContent = 'Saldo atual: ' + (li.dataset.stock || '0') + ' ' + (li.dataset.unit || '');
        linha.querySelector('.linha-opcoes').classList.add('hidden');
    });

    filtrarCategorias();
    filtroSet.addEventListener('change', filtrarCategorias);
})();
</script>
