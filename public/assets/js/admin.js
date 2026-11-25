document.addEventListener('DOMContentLoaded', () => {
    const page = document.body.dataset.page;
    const today = new Date().toISOString().split('T')[0];

    if (page === 'dashboard') initDashboardAdmin();
    if (page === 'murid') initMuridAdmin();
    if (page === 'verifikasi') initVerifikasiAdmin();
    if (page === 'kehadiran') initKehadiranAdmin(today);
    if (page === 'jurnal') initJurnalAdmin(today);
    if (page === 'pembayaran') initPembayaranAdmin(today);
    if (page === 'pengaturan') initPengaturanAdmin();
    if (page === 'akun') initAkunAdmin();
});

// ================= 1. DASHBOARD BERANDA =================
async function initDashboardAdmin() {
    try {
        const res = await App.apiGet('/api/admin.php?action=dashboard');
        const st = res.data.statistik;
        const lanjut = res.data.murid_lanjut || [];

        document.getElementById('statTotalMurid').textContent = `${st.total_murid} Murid`;
        document.getElementById('statKehadiran').textContent =
            `${st.kehadiran_terakhir.total_hadir} / ${st.kehadiran_terakhir.total_tercatat} Hadir`;
        document.getElementById('statTglHadir').textContent = `Tgl: ${st.kehadiran_terakhir.tanggal_pertemuan}`;
        document.getElementById('statPemasukan').textContent = App.formatRupiah(st.pemasukan_bulan);
        document.getElementById('statPendaftar').textContent = `${st.pendaftar_menunggu} Calon`;
        document.getElementById('badgeJumlahLanjut').textContent = `${lanjut.length} Murid`;

        const tbody = document.getElementById('tbodyMuridLanjut');
        tbody.innerHTML = lanjut.length === 0
            ? '<tr><td colspan="5">Belum ada murid yang mencapai Jilid 4 ke atas.</td></tr>'
            : lanjut.map(m => `
                <tr>
                    <td style="font-weight:700;">${App.escapeHtml(m.nama_murid)}</td>
                    <td>${App.escapeHtml(m.nama_orang_tua)}</td>
                    <td><span class="badge badge-success">${App.escapeHtml(m.jilid)}</span></td>
                    <td>Halaman ${Number(m.halaman)}</td>
                    <td>${App.escapeHtml(m.tanggal)}</td>
                </tr>
            `).join('');
    } catch (err) {
        App.showAlert('alertAdmin', err.message, 'danger');
    }
}

// ================= 2. MURID & ORANG TUA =================
function toggleModalMurid(show) {
    const modal = document.getElementById('modalTambahMurid');
    if (show) modal.classList.remove('hidden');
    else modal.classList.add('hidden');
}

function initMuridAdmin() {
    loadMuridList();

    document.getElementById('formCariMurid').addEventListener('submit', (e) => {
        e.preventDefault();
        const kw = document.getElementById('inputCariMurid').value.trim();
        document.getElementById('btnResetCari').classList.toggle('hidden', kw === '');
        loadMuridList(kw);
    });

    document.getElementById('btnResetCari').addEventListener('click', () => {
        document.getElementById('inputCariMurid').value = '';
        document.getElementById('btnResetCari').classList.add('hidden');
        loadMuridList('');
    });

    document.getElementById('formTambahMurid').addEventListener('submit', async (e) => {
        e.preventDefault();
        App.hideAlert('alertAdmin');
        const payload = Object.fromEntries(new FormData(e.target).entries());
        try {
            const res = await App.apiPost('/api/admin.php?action=murid_tambah', payload);
            App.showAlert('alertAdmin', res.message, 'success');
            e.target.reset();
            toggleModalMurid(false);
            loadMuridList();
        } catch (err) {
            App.showAlert('alertAdmin', err.message, 'danger');
            toggleModalMurid(false);
        }
    });
}

async function loadMuridList(keyword = '') {
    try {
        const res = await App.apiGet(`/api/admin.php?action=murid_list&cari=${encodeURIComponent(keyword)}`);
        const list = res.data || [];
        const tbody = document.getElementById('tbodyMurid');

        tbody.innerHTML = list.length === 0
            ? '<tr><td colspan="8">Data murid tidak ditemukan.</td></tr>'
            : list.map(m => `
                <tr>
                    <td>${Number(m.id_murid)}</td>
                    <td style="font-weight:700;">${App.escapeHtml(m.nama_murid)}</td>
                    <td>${App.escapeHtml(m.tanggal_lahir)}</td>
                    <td>${App.escapeHtml(m.alamat_murid)}</td>
                    <td>${App.escapeHtml(m.nama_orang_tua)}</td>
                    <td>${App.escapeHtml(m.no_telepon)}</td>
                    <td>${m.username ? `<span class="badge badge-info">${App.escapeHtml(m.username)}</span>` : '<span style="color:var(--text-muted);">Belum ada</span>'}</td>
                    <td>
                        <button type="button" class="btn btn-danger btn-sm" onclick="hapusMurid(${Number(m.id_murid)})">Hapus</button>
                    </td>
                </tr>
            `).join('');
    } catch (err) {
        App.showAlert('alertAdmin', err.message, 'danger');
    }
}

async function hapusMurid(idMurid) {
    if (!confirm('Hapus murid ini beserta seluruh riwayat jurnal, absensi, dan pembayarannya?')) return;
    try {
        const res = await App.apiPost('/api/admin.php?action=murid_hapus', { id_murid: idMurid });
        App.showAlert('alertAdmin', res.message, 'success');
        loadMuridList();
    } catch (err) {
        App.showAlert('alertAdmin', err.message, 'danger');
    }
}

// ================= 3. VERIFIKASI PENDAFTARAN =================
function initVerifikasiAdmin() {
    loadVerifikasiList();
}

async function loadVerifikasiList() {
    try {
        const res = await App.apiGet('/api/admin.php?action=verifikasi_list');
        const list = res.data || [];
        const tbody = document.getElementById('tbodyVerifikasi');

        tbody.innerHTML = list.length === 0
            ? '<tr><td colspan="8">Belum ada data pendaftaran online.</td></tr>'
            : list.map(r => {
                let badgeClass = 'badge-warning';
                if (r.status_verifikasi === 'Diterima') badgeClass = 'badge-success';
                else if (r.status_verifikasi === 'Ditolak') badgeClass = 'badge-danger';

                const aksiHtml = r.status_verifikasi === 'Menunggu'
                    ? `<div class="action-buttons">
                           <button type="button" class="btn btn-success btn-sm" onclick="prosesVerifikasi(${Number(r.id_daftar)}, 'Diterima')">Terima</button>
                           <button type="button" class="btn btn-danger btn-sm" onclick="prosesVerifikasi(${Number(r.id_daftar)}, 'Ditolak')">Tolak</button>
                       </div>`
                    : '<span style="color:var(--text-muted);">Selesai</span>';

                return `
                    <tr>
                        <td>${App.escapeHtml(r.tanggal_daftar)}</td>
                        <td style="font-weight:700;">${App.escapeHtml(r.nama_calon_murid)}</td>
                        <td>${App.escapeHtml(r.tanggal_lahir)}</td>
                        <td>${App.escapeHtml(r.alamat_murid)}</td>
                        <td>${App.escapeHtml(r.nama_orang_tua)}</td>
                        <td>${App.escapeHtml(r.no_telepon)}</td>
                        <td><span class="badge ${badgeClass}">${App.escapeHtml(r.status_verifikasi)}</span></td>
                        <td>${aksiHtml}</td>
                    </tr>
                `;
            }).join('');
    } catch (err) {
        App.showAlert('alertAdmin', err.message, 'danger');
    }
}

async function prosesVerifikasi(idDaftar, aksi) {
    if (!confirm(`Yakin ingin menandai pendaftaran ini sebagai ${aksi}?`)) return;
    try {
        const res = await App.apiPost('/api/admin.php?action=verifikasi_proses', { id_daftar: idDaftar, aksi });
        App.showAlert('alertAdmin', res.message, 'success');
        loadVerifikasiList();
    } catch (err) {
        App.showAlert('alertAdmin', err.message, 'danger');
    }
}

// ================= 4. KEHADIRAN MASSAL =================
function initKehadiranAdmin(today) {
    const inputTgl = document.getElementById('inputTglAbsen');
    inputTgl.value = today;
    loadKehadiranList(today);

    document.getElementById('btnMuatAbsen').addEventListener('click', () => {
        loadKehadiranList(inputTgl.value);
    });

    document.getElementById('formSimpanAbsen').addEventListener('submit', async (e) => {
        e.preventDefault();
        App.hideAlert('alertAdmin');
        const statusMap = {};
        document.querySelectorAll('#tbodyAbsen input[type="radio"]:checked').forEach(radio => {
            statusMap[radio.dataset.idMurid] = radio.value;
        });

        try {
            const res = await App.apiPost('/api/admin.php?action=kehadiran_simpan', {
                tanggal_pertemuan: inputTgl.value,
                status: statusMap
            });
            App.showAlert('alertAdmin', res.message, 'success');
        } catch (err) {
            App.showAlert('alertAdmin', err.message, 'danger');
        }
    });
}

async function loadKehadiranList(tanggal) {
    document.getElementById('labelTglAbsen').textContent = `Daftar Hadir Tanggal: ${tanggal}`;
    try {
        const res = await App.apiGet(`/api/admin.php?action=kehadiran_list&tanggal=${encodeURIComponent(tanggal)}`);
        const list = res.data || [];
        const tbody = document.getElementById('tbodyAbsen');

        tbody.innerHTML = list.length === 0
            ? '<tr><td colspan="3">Belum ada data murid aktif.</td></tr>'
            : list.map((row, idx) => {
                const st = row.status_kehadiran || 'Hadir';
                const id = Number(row.id_murid);
                return `
                    <tr>
                        <td>${idx + 1}</td>
                        <td style="font-weight:700;">${App.escapeHtml(row.nama_murid)}</td>
                        <td>
                            <label style="margin-right: 16px; color: var(--success); font-weight: 600;">
                                <input type="radio" name="absen_${id}" data-id-murid="${id}" value="Hadir" ${st === 'Hadir' ? 'checked' : ''}> Hadir
                            </label>
                            <label style="color: var(--danger); font-weight: 600;">
                                <input type="radio" name="absen_${id}" data-id-murid="${id}" value="Tidak Hadir" ${st === 'Tidak Hadir' ? 'checked' : ''}> Tidak Hadir
                            </label>
                        </td>
                    </tr>
                `;
            }).join('');
    } catch (err) {
        App.showAlert('alertAdmin', err.message, 'danger');
    }
}

// ================= 5. JURNAL PEMBELAJARAN =================
function initJurnalAdmin(today) {
    document.getElementById('tglJurnal').value = today;
    loadJurnalData(0);

    document.getElementById('filterMuridJurnal').addEventListener('change', (e) => {
        loadJurnalData(Number(e.target.value));
    });

    document.getElementById('formTambahJurnal').addEventListener('submit', async (e) => {
        e.preventDefault();
        App.hideAlert('alertAdmin');
        const payload = Object.fromEntries(new FormData(e.target).entries());
        try {
            const res = await App.apiPost('/api/admin.php?action=jurnal_tambah', payload);
            App.showAlert('alertAdmin', res.message, 'success');
            e.target.reset();
            document.getElementById('tglJurnal').value = today;
            loadJurnalData(Number(document.getElementById('filterMuridJurnal').value));
        } catch (err) {
            App.showAlert('alertAdmin', err.message, 'danger');
        }
    });
}

async function loadJurnalData(filterMurid = 0) {
    try {
        const res = await App.apiGet(`/api/admin.php?action=jurnal_list&id_murid=${filterMurid}`);
        const muridList = res.data.murid || [];
        const jurnalList = res.data.jurnal || [];

        const selInput = document.getElementById('selectMuridJurnal');
        const selFilter = document.getElementById('filterMuridJurnal');
        if (selInput.options.length <= 1) {
            muridList.forEach(m => {
                selInput.insertAdjacentHTML('beforeend', `<option value="${m.id_murid}">${App.escapeHtml(m.nama_murid)}</option>`);
                selFilter.insertAdjacentHTML('beforeend', `<option value="${m.id_murid}">${App.escapeHtml(m.nama_murid)}</option>`);
            });
        }

        const tbody = document.getElementById('tbodyJurnal');
        tbody.innerHTML = jurnalList.length === 0
            ? '<tr><td colspan="6">Belum ada catatan jurnal.</td></tr>'
            : jurnalList.map(j => `
                <tr>
                    <td>${App.escapeHtml(j.tanggal)}</td>
                    <td style="font-weight:700;">${App.escapeHtml(j.nama_murid)}</td>
                    <td><span class="badge badge-info">${App.escapeHtml(j.jilid)}</span></td>
                    <td>Hal. ${Number(j.halaman)}</td>
                    <td>${App.escapeHtml(j.catatan_pengajar || '-')}</td>
                    <td><button type="button" class="btn btn-danger btn-sm" onclick="hapusJurnal(${Number(j.id_jurnal)})">Hapus</button></td>
                </tr>
            `).join('');
    } catch (err) {
        App.showAlert('alertAdmin', err.message, 'danger');
    }
}

async function hapusJurnal(idJurnal) {
    if (!confirm('Hapus catatan jurnal ini?')) return;
    try {
        const res = await App.apiPost('/api/admin.php?action=jurnal_hapus', { id_jurnal: idJurnal });
        App.showAlert('alertAdmin', res.message, 'success');
        loadJurnalData(Number(document.getElementById('filterMuridJurnal').value));
    } catch (err) {
        App.showAlert('alertAdmin', err.message, 'danger');
    }
}

// ================= 6. PEMBAYARAN =================
function initPembayaranAdmin(today) {
    document.getElementById('tglBayar').value = today;
    loadPembayaranData();

    document.getElementById('formTambahBayar').addEventListener('submit', async (e) => {
        e.preventDefault();
        App.hideAlert('alertAdmin');
        const payload = Object.fromEntries(new FormData(e.target).entries());
        try {
            const res = await App.apiPost('/api/admin.php?action=pembayaran_tambah', payload);
            App.showAlert('alertAdmin', `${res.message} No. Transaksi: ${res.data.no_kuitansi}`, 'success');
            loadPembayaranData();
        } catch (err) {
            App.showAlert('alertAdmin', err.message, 'danger');
        }
    });
}

async function loadPembayaranData() {
    try {
        const res = await App.apiGet('/api/admin.php?action=pembayaran_list');
        const muridList = res.data.murid || [];
        const pembayaranList = res.data.pembayaran || [];

        const selMurid = document.getElementById('selectMuridBayar');
        if (selMurid.options.length <= 1) {
            muridList.forEach(m => {
                selMurid.insertAdjacentHTML('beforeend', `<option value="${m.id_murid}">${App.escapeHtml(m.nama_murid)}</option>`);
            });
        }

        const tbody = document.getElementById('tbodyBayar');
        tbody.innerHTML = pembayaranList.length === 0
            ? '<tr><td colspan="6">Belum ada data pembayaran.</td></tr>'
            : pembayaranList.map(p => `
                <tr>
                    <td style="font-weight:600; color:var(--navy);">${App.escapeHtml(p.no_kuitansi || '-')}</td>
                    <td>${App.escapeHtml(p.tanggal)}</td>
                    <td style="font-weight:700;">${App.escapeHtml(p.nama_murid)}</td>
                    <td>${App.escapeHtml(p.jenis_pembayaran)}</td>
                    <td>${App.formatRupiah(p.jumlah)}</td>
                    <td><span class="badge ${p.status_pembayaran === 'Lunas' ? 'badge-success' : 'badge-warning'}">${App.escapeHtml(p.status_pembayaran)}</span></td>
                </tr>
            `).join('');
    } catch (err) {
        App.showAlert('alertAdmin', err.message, 'danger');
    }
}

// ================= 7. PENGATURAN WEB & TESTIMONI =================
function initPengaturanAdmin() {
    loadPengaturanData();

    document.getElementById('formPengaturanWeb').addEventListener('submit', async (e) => {
        e.preventDefault();
        App.hideAlert('alertAdmin');
        const payload = Object.fromEntries(new FormData(e.target).entries());
        try {
            const res = await App.apiPost('/api/admin.php?action=pengaturan_update', payload);
            App.showAlert('alertAdmin', res.message, 'success');
        } catch (err) {
            App.showAlert('alertAdmin', err.message, 'danger');
        }
    });

    document.getElementById('formTambahTestimoni').addEventListener('submit', async (e) => {
        e.preventDefault();
        App.hideAlert('alertAdmin');
        const payload = Object.fromEntries(new FormData(e.target).entries());
        try {
            const res = await App.apiPost('/api/admin.php?action=testimoni_tambah', payload);
            App.showAlert('alertAdmin', res.message, 'success');
            e.target.reset();
            loadPengaturanData();
        } catch (err) {
            App.showAlert('alertAdmin', err.message, 'danger');
        }
    });
}

async function loadPengaturanData() {
    try {
        const res = await App.apiGet('/api/admin.php?action=pengaturan_get');
        const info = res.data.info || {};
        const testimoni = res.data.testimoni || [];

        document.getElementById('setNamaBimbel').value = info.nama_bimbel || '';
        document.getElementById('setProfilSingkat').value = info.profil_singkat || '';
        document.getElementById('setNoWa').value = info.no_whatsapp || '';
        document.getElementById('setJadwal').value = info.jadwal_belajar || '';
        document.getElementById('setAlamat').value = info.lokasi_alamat || '';
        document.getElementById('setGmaps').value = info.link_gmaps || '';
        document.getElementById('setProgram').value = info.rincian_program || '';
        document.getElementById('setBiaya').value = info.rincian_biaya || '';

        const listEl = document.getElementById('listTestimoniAdmin');
        listEl.innerHTML = testimoni.length === 0
            ? '<p style="color: var(--text-muted);">Belum ada testimoni.</p>'
            : testimoni.map(t => {
                const isTampil = t.status_tampil === 'Tampil';
                const nextStatus = isTampil ? 'Sembunyikan' : 'Tampil';
                const btnClass = isTampil ? 'btn-outline' : 'btn-success';
                return `
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:10px; padding:10px 0; border-bottom:1px solid var(--border-color);">
                        <div>
                            <strong>${App.escapeHtml(t.nama_orang_tua)}</strong>
                            <p style="font-size:0.88rem; color:var(--text-muted);">"${App.escapeHtml(t.isi_testimoni)}"</p>
                        </div>
                        <button type="button" class="btn ${btnClass} btn-sm" onclick="toggleTestimoni(${Number(t.id_testimoni)}, '${nextStatus}')">
                            ${nextStatus}
                        </button>
                    </div>
                `;
            }).join('');
    } catch (err) {
        App.showAlert('alertAdmin', err.message, 'danger');
    }
}

async function toggleTestimoni(idTestimoni, statusBaru) {
    try {
        const res = await App.apiPost('/api/admin.php?action=testimoni_toggle', {
            id_testimoni: idTestimoni,
            status_baru: statusBaru
        });
        App.showAlert('alertAdmin', res.message, 'success');
        loadPengaturanData();
    } catch (err) {
        App.showAlert('alertAdmin', err.message, 'danger');
    }
}

// ================= 8. MANAJEMEN AKUN =================
let cacheAkunList = [];

function initAkunAdmin() {
    loadAkunData();

    const selRole = document.getElementById('selectRoleAkun');
    const grupOrtu = document.getElementById('grupPilihOrtu');
    selRole.addEventListener('change', () => {
        grupOrtu.classList.toggle('hidden', selRole.value !== 'orang_tua');
    });

    document.getElementById('formTambahAkun').addEventListener('submit', async (e) => {
        e.preventDefault();
        App.hideAlert('alertAdmin');
        const payload = Object.fromEntries(new FormData(e.target).entries());
        try {
            const res = await App.apiPost('/api/admin.php?action=akun_tambah', payload);
            App.showAlert('alertAdmin', res.message, 'success');
            e.target.reset();
            grupOrtu.classList.remove('hidden');
            loadAkunData();
        } catch (err) {
            App.showAlert('alertAdmin', err.message, 'danger');
        }
    });

    document.getElementById('formEditAkun').addEventListener('submit', async (e) => {
        e.preventDefault();
        App.hideAlert('alertAdmin');
        const payload = Object.fromEntries(new FormData(e.target).entries());
        try {
            const res = await App.apiPost('/api/admin.php?action=akun_ubah', payload);
            App.showAlert('alertAdmin', res.message, 'success');
            tutupModalEditAkun();
            loadAkunData();
        } catch (err) {
            App.showAlert('alertAdmin', err.message, 'danger');
            tutupModalEditAkun();
        }
    });
}

async function loadAkunData() {
    try {
        const res = await App.apiGet('/api/admin.php?action=akun_list');
        cacheAkunList = res.data.akun || [];
        const ortuList = res.data.ortu_tanpa_akun || [];

        const selOrtu = document.getElementById('selectOrtuTanpaAkun');
        selOrtu.innerHTML = '<option value="">-- Pilih Orang Tua --</option>' +
            ortuList.map(o => `<option value="${o.id_orang_tua}">${App.escapeHtml(o.nama_orang_tua)} (${App.escapeHtml(o.no_telepon)})</option>`).join('');

        const tbody = document.getElementById('tbodyAkun');
        tbody.innerHTML = cacheAkunList.length === 0
            ? '<tr><td colspan="5">Belum ada akun terdaftar.</td></tr>'
            : cacheAkunList.map((u, idx) => `
                <tr>
                    <td>${Number(u.id_user)}</td>
                    <td><span class="badge ${u.role === 'admin' ? 'badge-warning' : 'badge-info'}">${u.role === 'admin' ? 'Admin' : 'Orang Tua'}</span></td>
                    <td style="font-weight:700;">${App.escapeHtml(u.username)}</td>
                    <td>${u.nama_orang_tua ? App.escapeHtml(u.nama_orang_tua) : '<span style="color:var(--text-muted);">- (Akun Pengelola)</span>'}</td>
                    <td>
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="bukaModalEditAkun(${idx})">Ubah Nama / Password</button>
                    </td>
                </tr>
            `).join('');
    } catch (err) {
        App.showAlert('alertAdmin', err.message, 'danger');
    }
}

function bukaModalEditAkun(index) {
    const u = cacheAkunList[index];
    if (!u) return;
    document.getElementById('editIdUser').value = u.id_user;
    document.getElementById('editUsername').value = u.username;
    document.getElementById('editPasswordBaru').value = '';

    const grupNama = document.getElementById('grupEditNamaOrtu');
    if (u.role === 'orang_tua' && u.id_orang_tua) {
        grupNama.classList.remove('hidden');
        document.getElementById('editNamaOrtu').value = u.nama_orang_tua || '';
    } else {
        grupNama.classList.add('hidden');
        document.getElementById('editNamaOrtu').value = '';
    }
    document.getElementById('modalEditAkun').classList.remove('hidden');
}

function tutupModalEditAkun() {
    document.getElementById('modalEditAkun').classList.add('hidden');
}