// Konfirmasi sebelum aksi hapus / void / pembatalan
document.addEventListener('click', function (e) {
  const el = e.target.closest('[data-confirm]');
  if (el) {
    const msg = el.getAttribute('data-confirm') || 'Apakah Anda yakin?';
    if (!confirm(msg)) {
      e.preventDefault();
    }
  }
});

// Auto-hide alert flash message setelah 4 detik
document.addEventListener('DOMContentLoaded', function () {
  const alerts = document.querySelectorAll('.alert[data-autohide]');
  alerts.forEach(function (a) {
    setTimeout(function () {
      a.style.transition = 'opacity .5s';
      a.style.opacity = '0';
      setTimeout(() => a.remove(), 500);
    }, 4000);
  });
});

// Hitung kembalian otomatis di form pembayaran kasir
function hitungKembalian() {
  const total = parseFloat(document.getElementById('total_bayar')?.dataset.total || 0);
  const dibayarEl = document.getElementById('jumlah_dibayar');
  const kembalianEl = document.getElementById('kembalian_display');
  if (!dibayarEl || !kembalianEl) return;
  const dibayar = parseFloat(dibayarEl.value || 0);
  const kembalian = dibayar - total;
  kembalianEl.textContent = 'Rp ' + (kembalian > 0 ? kembalian : 0).toLocaleString('id-ID');
  kembalianEl.style.color = kembalian < 0 ? '#c62828' : '#2e7d32';
}
