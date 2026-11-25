document.addEventListener('DOMContentLoaded', () => {
    // 1. LOGIKA HALAMAN BERANDA (index.html)
    if (document.getElementById('heroNamaBimbel')) {
        loadHomeData();
    }

    // 2. LOGIKA FORMULIR PRA-PENDAFTARAN (daftar.html)
    const formDaftar = document.getElementById('formDaftar');
    if (formDaftar) {
        formDaftar.addEventListener('submit', async (e) => {
            e.preventDefault();
            App.hideAlert('alertDaftar');
            const payload = Object.fromEntries(new FormData(formDaftar).entries());

            try {
                const res = await App.apiPost('/api/public.php?action=daftar', payload);
                App.showAlert('alertDaftar', res.message, 'success');
                formDaftar.reset();
            } catch (err) {
                App.showAlert('alertDaftar', err.message, 'danger');
            }
        });
    }

    // 3. LOGIKA FORMULIR LOGIN (login.html)
    const formLogin = document.getElementById('formLogin');
    if (formLogin) {
        formLogin.addEventListener('submit', async (e) => {
            e.preventDefault();
            App.hideAlert('alertLogin');
            const payload = Object.fromEntries(new FormData(formLogin).entries());

            try {
                const res = await App.apiPost('/api/auth.php?action=login', payload);
                const role = res.data.role;
                window.location.href = role === 'admin' ? '/admin/dashboard.html' : '/ortu/dashboard.html';
            } catch (err) {
                App.showAlert('alertLogin', err.message, 'danger');
            }
        });
    }
});

async function loadHomeData() {
    try {
        const res = await App.apiGet('/api/public.php?action=home');
        const info = res.data.info || {};
        const testimoni = res.data.testimoni || [];

        document.getElementById('heroNamaBimbel').textContent = info.nama_bimbel || 'Nusa Les Calistung';
        document.getElementById('heroProfil').textContent = info.profil_singkat || '';
        document.getElementById('isiProgram').textContent = info.rincian_program || '';
        document.getElementById('isiBiaya').textContent = info.rincian_biaya || '';
        document.getElementById('isiJadwal').textContent = info.jadwal_belajar || '';
        document.getElementById('isiAlamat').textContent = info.lokasi_alamat || '';

        const btnGmaps = document.getElementById('btnGmaps');
        if (btnGmaps) btnGmaps.href = info.link_gmaps || '#';

        const waNum = String(info.no_whatsapp || '6281234567890').replace(/[^0-9]/g, '');
        const waText = encodeURIComponent('Halo Admin Nusa Les Calistung, saya ingin bertanya mengenai informasi pendaftaran les.');
        document.getElementById('btnWhatsapp').href = `https://wa.me/${waNum}?text=${waText}`;

        const listEl = document.getElementById('listTestimoni');
        if (testimoni.length === 0) {
            listEl.innerHTML = '<p style="color: var(--text-muted);">Belum ada ulasan yang ditampilkan.</p>';
        } else {
            listEl.innerHTML = testimoni.map(item => `
                <div style="background: var(--bg-body); padding: 14px; border-radius: 6px; border: 1px solid var(--border-color);">
                    <p style="font-style: italic; margin-bottom: 8px;">"${App.escapeHtml(item.isi_testimoni)}"</p>
                    <strong>${App.escapeHtml(item.nama_orang_tua)}</strong>
                    <small style="color: var(--text-muted);"> &bull; ${App.escapeHtml(item.tanggal_dibuat)}</small>
                </div>
            `).join('');
        }
    } catch (err) {
        App.showAlert('alertHome', err.message, 'danger');
    }
}