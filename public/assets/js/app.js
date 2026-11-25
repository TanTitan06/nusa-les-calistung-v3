const App = {
    escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    },

    formatRupiah(angka) {
        return 'Rp ' + Number(angka || 0).toLocaleString('id-ID');
    },

    showAlert(containerId, message, type = 'success') {
        const el = document.getElementById(containerId);
        if (!el) return;
        el.className = `alert alert-${type}`;
        el.textContent = message;
        el.classList.remove('hidden');
    },

    hideAlert(containerId) {
        const el = document.getElementById(containerId);
        if (el) el.classList.add('hidden');
    },

    async apiGet(url) {
        const res = await fetch(url, { credentials: 'same-origin' });
        const json = await res.json();
        if (res.status === 401) {
            window.location.href = '/login.html';
            throw new Error(json.message || 'Sesi berakhir');
        }
        if (!res.ok || json.status !== 'success') {
            throw new Error(json.message || 'Gagal memuat data');
        }
        return json;
    },

    async apiPost(url, payload = {}) {
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify(payload)
        });
        const json = await res.json();
        if (res.status === 401 && !url.includes('action=login')) {
            window.location.href = '/login.html';
            throw new Error(json.message || 'Sesi berakhir');
        }
        if (!res.ok || json.status !== 'success') {
            throw new Error(json.message || 'Gagal memproses permintaan');
        }
        return json;
    },

    async logout() {
        try {
            await this.apiGet('/api/auth.php?action=logout');
        } catch (e) {}
        window.location.href = '/login.html';
    }
};