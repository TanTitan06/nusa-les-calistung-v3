document.addEventListener('DOMContentLoaded', () => {
    loadDashboardOrtu();
});

async function loadDashboardOrtu(idMurid = 0) {
    App.hideAlert('alertOrtu');
    try {
        const url = idMurid > 0 ? `/api/ortu.php?id_murid=${idMurid}` : '/api/ortu.php';
        const res = await App.apiGet(url);
        const data = res.data;

        document.getElementById('sapaanOrtu').textContent =
            `Halo, ${data.profil_ortu.nama_orang_tua} (${data.profil_ortu.no_telepon})`;

        // Dropdown jika orang tua memiliki lebih dari 1 anak
        const pemilihEl = document.getElementById('pemilihAnak');
        if (data.daftar_anak && data.daftar_anak.length > 1) {
            const options = data.daftar_anak.map(a =>
                `<option value="${a.id_murid}" ${data.detail_anak && Number(a.id_murid) === Number(data.detail_anak.id_murid) ? 'selected' : ''}>
                    ${App.escapeHtml(a.nama_murid)}
                </option>`
            ).join('');
            pemilihEl.innerHTML = `<label style="font-size:0.85rem;">Pilih Anak:</label>
                <select class="form-control" style="width:auto; padding:5px 10px;" onchange="loadDashboardOrtu(this.value)">${options}</select>`;
        } else {
            pemilihEl.innerHTML = '';
        }

        const anak = data.detail_anak;
        if (anak) {
            document.getElementById('namaMurid').textContent = anak.nama_murid;
            document.getElementById('infoMurid').textContent = `Tgl Lahir: ${anak.tanggal_lahir} | Alamat: ${anak.alamat_murid}`;
        }

        const last = data.jurnal_terakhir;
        if (last) {
            document.getElementById('capaianTerakhir').textContent = `${last.jilid} - Halaman ${last.halaman}`;
            document.getElementById('catatanTerakhir').textContent = `(${last.tanggal}) ${last.catatan_pengajar || 'Tidak ada catatan khusus.'}`;
        } else {
            document.getElementById('capaianTerakhir').textContent = 'Belum ada catatan';
            document.getElementById('catatanTerakhir').textContent = '-';
        }

        // Render Tabel Jurnal
        const tbJurnal = document.getElementById('tbodyJurnalOrtu');
        tbJurnal.innerHTML = data.riwayat_jurnal.length === 0
            ? '<tr><td colspan="4">Belum ada riwayat jurnal pembelajaran.</td></tr>'
            : data.riwayat_jurnal.map(j => `
                <tr>
                    <td>${App.escapeHtml(j.tanggal)}</td>
                    <td><span class="badge badge-info">${App.escapeHtml(j.jilid)}</span></td>
                    <td>Halaman ${Number(j.halaman)}</td>
                    <td>${App.escapeHtml(j.catatan_pengajar || '-')}</td>
                </tr>
            `).join('');

        // Render Tabel Kehadiran
        const tbHadir = document.getElementById('tbodyHadirOrtu');
        tbHadir.innerHTML = data.riwayat_hadir.length === 0
            ? '<tr><td colspan="2">Belum ada data kehadiran.</td></tr>'
            : data.riwayat_hadir.map(h => `
                <tr>
                    <td>${App.escapeHtml(h.tanggal_pertemuan)}</td>
                    <td><span class="badge ${h.status_kehadiran === 'Hadir' ? 'badge-success' : 'badge-danger'}">${App.escapeHtml(h.status_kehadiran)}</span></td>
                </tr>
            `).join('');

        // Render Tabel Pembayaran
        const tbBayar = document.getElementById('tbodyBayarOrtu');
        tbBayar.innerHTML = data.riwayat_bayar.length === 0
            ? '<tr><td colspan="4">Belum ada riwayat pembayaran.</td></tr>'
            : data.riwayat_bayar.map(p => `
                <tr>
                    <td>${App.escapeHtml(p.tanggal)}</td>
                    <td>${App.escapeHtml(p.jenis_pembayaran)}</td>
                    <td>${App.formatRupiah(p.jumlah)}</td>
                    <td><span class="badge ${p.status_pembayaran === 'Lunas' ? 'badge-success' : 'badge-warning'}">${App.escapeHtml(p.status_pembayaran)}</span></td>
                </tr>
            `).join('');

    } catch (err) {
        App.showAlert('alertOrtu', err.message, 'danger');
    }
}