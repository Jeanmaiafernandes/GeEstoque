import Chart from 'chart.js/auto';

document.addEventListener('DOMContentLoaded', () => {
    initCharts();
    initScanner();
});

/* ------------------------------------------------------------------ Gráficos do painel */
function initCharts() {
    const dataEl = document.getElementById('kpi-data');
    if (!dataEl) {
        return;
    }

    let data;
    try {
        data = JSON.parse(dataEl.textContent);
    } catch (e) {
        return;
    }

    const c = {
        teal: '#0f766e', cyan: '#0891b2', emerald: '#059669',
        red: '#dc2626', amber: '#d97706', indigo: '#4f46e5', violet: '#7c3aed',
    };

    Chart.defaults.font.family = "'Instrument Sans', ui-sans-serif, system-ui, sans-serif";
    Chart.defaults.color = '#475569';

    const intAxis = { beginAtZero: true, ticks: { precision: 0 } };
    const base = { responsive: true, maintainAspectRatio: false };

    const movEl = document.getElementById('chart-mov');
    if (movEl && data.movimentacoes) {
        new Chart(movEl, {
            type: 'bar',
            data: {
                labels: data.movimentacoes.labels,
                datasets: [
                    { label: 'Entradas', data: data.movimentacoes.entradas, backgroundColor: c.emerald, borderRadius: 4 },
                    { label: 'Saídas', data: data.movimentacoes.saidas, backgroundColor: c.red, borderRadius: 4 },
                ],
            },
            options: { ...base, plugins: { legend: { position: 'bottom' } }, scales: { y: intAxis } },
        });
    }

    const setorEl = document.getElementById('chart-setor');
    if (setorEl && data.consumoSetor) {
        new Chart(setorEl, {
            type: 'doughnut',
            data: { labels: data.consumoSetor.labels, datasets: [{ data: data.consumoSetor.data, backgroundColor: [c.teal, c.indigo, c.amber, c.cyan, c.violet] }] },
            options: { ...base, plugins: { legend: { position: 'bottom' } } },
        });
    }

    const catEl = document.getElementById('chart-cat');
    if (catEl && data.categorias) {
        new Chart(catEl, {
            type: 'bar',
            data: { labels: data.categorias.labels, datasets: [{ label: 'Materiais', data: data.categorias.data, backgroundColor: c.teal, borderRadius: 4 }] },
            options: { ...base, indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: intAxis } },
        });
    }

    const topEl = document.getElementById('chart-top');
    if (topEl && data.topItens) {
        new Chart(topEl, {
            type: 'bar',
            data: { labels: data.topItens.labels, datasets: [{ label: 'Movimentações', data: data.topItens.data, backgroundColor: c.cyan, borderRadius: 4 }] },
            options: { ...base, indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: intAxis } },
        });
    }
}

/* ------------------------------------------------------------------ Leitura por câmera (movimentações) */
function initScanner() {
    const openBtn = document.getElementById('scan-open');
    if (!openBtn) {
        return;
    }

    const modal = document.getElementById('scan-modal');
    const closeBtn = document.getElementById('scan-close');
    const statusEl = document.getElementById('scan-status');
    const container = document.getElementById('linhas');

    let scanner = null;
    let lastCode = '';
    let lastTime = 0;

    openBtn.addEventListener('click', async () => {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        statusEl.textContent = 'Iniciando a câmera…';

        try {
            const { Html5Qrcode } = await import('html5-qrcode');
            scanner = new Html5Qrcode('scan-reader');
            await scanner.start({ facingMode: 'environment' }, { fps: 10, qrbox: 250 }, onScan, () => {});
            statusEl.textContent = 'Aponte para o código…';
        } catch (e) {
            statusEl.textContent = 'Não foi possível acessar a câmera. Na rede local é necessário HTTPS (ou use um leitor USB).';
        }
    });

    closeBtn.addEventListener('click', stop);

    function stop() {
        if (scanner) {
            scanner.stop().then(() => scanner.clear()).catch(() => {});
            scanner = null;
        }
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function onScan(text) {
        const code = (text || '').trim();
        const now = Date.now();
        if (!code || (code === lastCode && now - lastTime < 1500)) {
            return;
        }
        lastCode = code;
        lastTime = now;
        preencherLinha(code);
    }

    function preencherLinha(code) {
        const itens = window.MOV_ITENS || [];
        const alvo = code.toLowerCase();
        const item = itens.find((it) =>
            (it.code || '').toLowerCase() === alvo
            || (it.barcode && String(it.barcode).toLowerCase() === alvo));

        if (!item) {
            statusEl.textContent = 'Não encontrado: ' + code;
            return;
        }

        // Acha a primeira linha sem material escolhido, ou adiciona uma nova.
        let linha = [...container.querySelectorAll('.linha')].find((l) => !l.querySelector('.linha-item-id').value);
        if (!linha) {
            document.getElementById('add-linha').click();
            const all = container.querySelectorAll('.linha');
            linha = all[all.length - 1];
        }

        linha.querySelector('.linha-busca').value = item.label;
        linha.querySelector('.linha-item-id').value = item.id;
        const saldo = linha.querySelector('.linha-saldo');
        if (saldo) {
            saldo.textContent = 'Saldo atual: ' + (item.stock || '0') + ' ' + (item.unit || '');
        }
        const amount = linha.querySelector('input[name$="[amount]"]');
        if (amount) {
            amount.focus();
        }

        statusEl.textContent = 'Adicionado: ' + item.label;
    }
}
