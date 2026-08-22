<script setup>
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
  <Head title="Kebijakan Privasi — FolkraCS" />

  <div class="bg-white font-sans antialiased text-gray-900">

    <!-- Navbar -->
    <header class="sticky top-0 z-50 bg-white/95 backdrop-blur border-b border-gray-100">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
          <Link href="/" class="flex items-center">
            <img src="/logo/logo_dark.jpg" alt="FolkraCS" class="h-8 w-auto" />
          </Link>
          <Link href="/" class="text-sm text-gray-500 hover:text-gray-900 transition-colors">
            ← Kembali ke Beranda
          </Link>
        </div>
      </div>
    </header>

    <!-- Content -->
    <main class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-16">

      <div class="mb-10">
        <h1 class="text-4xl font-bold text-gray-900 mb-3">Kebijakan Privasi</h1>
        <p class="text-gray-500 text-sm">Terakhir diperbarui: 22 Agustus 2026</p>
      </div>

      <div class="prose prose-gray max-w-none space-y-10 text-gray-700 leading-relaxed">

        <section>
          <p>
            FolkraCS ("kami", "layanan kami") berkomitmen untuk melindungi privasi pengguna dan klien bisnis kami.
            Kebijakan Privasi ini menjelaskan bagaimana kami mengumpulkan, menggunakan, menyimpan, dan melindungi
            informasi Anda saat menggunakan platform FolkraCS.
          </p>
          <p class="mt-4">
            Dengan menggunakan layanan FolkraCS, Anda menyetujui pengumpulan dan penggunaan informasi sesuai dengan
            kebijakan ini.
          </p>
        </section>

        <section>
          <h2 class="text-xl font-semibold text-gray-900 mb-4">1. Informasi yang Kami Kumpulkan</h2>

          <h3 class="font-semibold text-gray-800 mb-2">a. Informasi Akun</h3>
          <p>Saat Anda mendaftar atau menggunakan FolkraCS, kami mengumpulkan:</p>
          <ul class="list-disc ml-6 mt-2 space-y-1 text-sm">
            <li>Nama lengkap dan alamat email</li>
            <li>Nama bisnis dan jenis usaha</li>
            <li>Kredensial autentikasi (disimpan dalam bentuk terenkripsi)</li>
            <li>Informasi pembayaran dan penagihan</li>
          </ul>

          <h3 class="font-semibold text-gray-800 mt-5 mb-2">b. Informasi Penggunaan Layanan</h3>
          <ul class="list-disc ml-6 mt-2 space-y-1 text-sm">
            <li>Konfigurasi AI dan instruksi sistem yang Anda buat</li>
            <li>Konten knowledge base yang Anda unggah</li>
            <li>Log percakapan antara AI dan pelanggan Anda</li>
            <li>Data sesi dan log akses layanan</li>
          </ul>

          <h3 class="font-semibold text-gray-800 mt-5 mb-2">c. Data Pelanggan Bisnis Anda</h3>
          <p>
            Saat platform kami memproses pesan dari pelanggan bisnis Anda melalui WhatsApp atau Instagram, kami
            memproses informasi seperti:
          </p>
          <ul class="list-disc ml-6 mt-2 space-y-1 text-sm">
            <li>Nomor WhatsApp atau ID akun Instagram pengirim pesan</li>
            <li>Nama tampilan (push name) pengguna WhatsApp</li>
            <li>Isi percakapan yang diproses oleh AI</li>
          </ul>
          <p class="mt-3 text-sm text-gray-600 bg-gray-50 border border-gray-200 rounded-lg p-4">
            <strong>Catatan Penting:</strong> Data pelanggan bisnis Anda diproses atas dasar instruksi dari Anda
            (selaku pengelola bisnis) untuk menyediakan layanan customer service otomatis. Anda bertanggung jawab
            untuk memastikan pelanggan Anda mengetahui bahwa percakapan mereka diproses oleh AI.
          </p>
        </section>

        <section>
          <h2 class="text-xl font-semibold text-gray-900 mb-4">2. Bagaimana Kami Menggunakan Informasi</h2>
          <p>Kami menggunakan informasi yang dikumpulkan untuk:</p>
          <ul class="list-disc ml-6 mt-3 space-y-2 text-sm">
            <li>Menyediakan, mengoperasikan, dan meningkatkan layanan FolkraCS</li>
            <li>Memproses pesan pelanggan bisnis Anda melalui AI</li>
            <li>Mengirimkan notifikasi eskalasi kepada tim bisnis Anda</li>
            <li>Mengelola akun, langganan, dan penagihan</li>
            <li>Memberikan dukungan teknis dan layanan pelanggan</li>
            <li>Memenuhi kewajiban hukum yang berlaku di Indonesia</li>
          </ul>
          <p class="mt-4">
            Kami <strong>tidak</strong> menggunakan data percakapan pelanggan bisnis Anda untuk melatih model AI
            secara umum, menjual kepada pihak ketiga, atau tujuan pemasaran tanpa persetujuan eksplisit Anda.
          </p>
        </section>

        <section>
          <h2 class="text-xl font-semibold text-gray-900 mb-4">3. Layanan Pihak Ketiga</h2>
          <p>FolkraCS terintegrasi dengan layanan pihak ketiga berikut untuk mengoperasikan platform:</p>

          <div class="mt-4 space-y-4">
            <div class="border border-gray-200 rounded-xl p-5">
              <h3 class="font-semibold text-gray-800 mb-1">Meta (Facebook & Instagram)</h3>
              <p class="text-sm text-gray-600">
                Untuk integrasi Instagram DM, kami menggunakan Instagram Graph API dari Meta. Data pesan diproses
                sesuai dengan <a href="https://www.facebook.com/privacy/policy/" target="_blank" class="text-indigo-600 hover:underline">Kebijakan Privasi Meta</a>.
                Kami hanya mengakses data yang diperlukan untuk membalas pesan pelanggan bisnis Anda.
              </p>
            </div>

            <div class="border border-gray-200 rounded-xl p-5">
              <h3 class="font-semibold text-gray-800 mb-1">WAHA (WhatsApp API)</h3>
              <p class="text-sm text-gray-600">
                Untuk integrasi WhatsApp, kami menggunakan server WAHA yang dikonfigurasi oleh Anda. Data pesan
                WhatsApp diproses melalui infrastruktur yang Anda kendalikan.
              </p>
            </div>

            <div class="border border-gray-200 rounded-xl p-5">
              <h3 class="font-semibold text-gray-800 mb-1">OpenRouter / Model AI</h3>
              <p class="text-sm text-gray-600">
                Pesan diproses oleh model AI (GPT, Claude, Gemini, dll.) melalui OpenRouter. Konten percakapan
                dikirim ke API model AI untuk menghasilkan respons. Penggunaan data mengikuti kebijakan privasi
                masing-masing penyedia model AI.
              </p>
            </div>
          </div>
        </section>

        <section>
          <h2 class="text-xl font-semibold text-gray-900 mb-4">4. Penyimpanan dan Keamanan Data</h2>
          <ul class="list-disc ml-6 space-y-2 text-sm">
            <li>Data disimpan di server yang berlokasi di Indonesia atau kawasan Asia Tenggara</li>
            <li>Semua transmisi data menggunakan enkripsi HTTPS/TLS</li>
            <li>Token akses dan API key disimpan dalam bentuk terenkripsi di database</li>
            <li>Akses ke data dibatasi berdasarkan peran dan hak akses (multi-tenant isolation)</li>
            <li>Log percakapan disimpan selama masa langganan aktif dan 90 hari setelah berakhir</li>
            <li>Kami melakukan backup data secara rutin untuk mencegah kehilangan data</li>
          </ul>
        </section>

        <section>
          <h2 class="text-xl font-semibold text-gray-900 mb-4">5. Berbagi Informasi</h2>
          <p>Kami <strong>tidak menjual</strong> informasi pribadi Anda. Kami dapat berbagi informasi dalam kondisi berikut:</p>
          <ul class="list-disc ml-6 mt-3 space-y-2 text-sm">
            <li><strong>Penyedia layanan:</strong> Mitra teknis yang membantu mengoperasikan platform (hosting, AI API, dll.), dengan kewajiban kerahasiaan</li>
            <li><strong>Kewajiban hukum:</strong> Jika diwajibkan oleh hukum Indonesia atau perintah pengadilan yang sah</li>
            <li><strong>Perlindungan hak:</strong> Untuk melindungi hak, properti, atau keselamatan FolkraCS dan penggunanya</li>
            <li><strong>Dengan persetujuan Anda:</strong> Dalam situasi lain, hanya dengan izin eksplisit dari Anda</li>
          </ul>
        </section>

        <section>
          <h2 class="text-xl font-semibold text-gray-900 mb-4">6. Hak-Hak Anda</h2>
          <p>Sebagai pengguna FolkraCS, Anda memiliki hak untuk:</p>
          <ul class="list-disc ml-6 mt-3 space-y-2 text-sm">
            <li><strong>Mengakses</strong> data pribadi yang kami simpan tentang Anda</li>
            <li><strong>Memperbarui</strong> atau mengoreksi informasi yang tidak akurat</li>
            <li><strong>Menghapus</strong> akun dan data terkait (dengan mengajukan permintaan kepada kami)</li>
            <li><strong>Mengekspor</strong> data percakapan dalam format yang dapat dibaca mesin</li>
            <li><strong>Membatasi pemrosesan</strong> data dalam kondisi tertentu</li>
            <li><strong>Mencabut izin</strong> integrasi Instagram atau WhatsApp kapan saja</li>
          </ul>
          <p class="mt-4 text-sm">
            Untuk menggunakan hak-hak tersebut, hubungi kami di
            <a href="mailto:privacy@folkra.co" class="text-indigo-600 hover:underline">privacy@folkra.co</a>.
            Kami akan merespons dalam 14 hari kerja.
          </p>
        </section>

        <section>
          <h2 class="text-xl font-semibold text-gray-900 mb-4">7. Cookie dan Teknologi Pelacakan</h2>
          <p>FolkraCS menggunakan cookie untuk:</p>
          <ul class="list-disc ml-6 mt-3 space-y-1 text-sm">
            <li>Menjaga sesi login Anda tetap aktif</li>
            <li>Melindungi dari serangan CSRF (Cross-Site Request Forgery)</li>
            <li>Menyimpan preferensi tampilan</li>
          </ul>
          <p class="mt-3 text-sm">
            Kami tidak menggunakan cookie pihak ketiga untuk pelacakan iklan atau analitik eksternal.
          </p>
        </section>

        <section>
          <h2 class="text-xl font-semibold text-gray-900 mb-4">8. Kebijakan Khusus Instagram & Meta</h2>
          <p>
            Dalam rangka menggunakan Instagram Graph API, FolkraCS mematuhi ketentuan platform Meta:
          </p>
          <ul class="list-disc ml-6 mt-3 space-y-2 text-sm">
            <li>Kami hanya mengakses data Instagram yang diperlukan untuk membalas pesan masuk dari pelanggan bisnis Anda</li>
            <li>Token akses Instagram disimpan dengan aman dan hanya digunakan untuk keperluan bisnis Anda yang telah Anda otorisasi</li>
            <li>Anda dapat mencabut akses FolkraCS ke akun Instagram Anda kapan saja melalui halaman Connections di dashboard atau melalui pengaturan Meta Business Suite</li>
            <li>Kami tidak mengakses, menyimpan, atau memproses data Instagram pengguna di luar keperluan layanan yang Anda aktifkan</li>
            <li>Data pesan Instagram tidak dibagikan kepada pihak ketiga selain penyedia model AI yang Anda pilih</li>
          </ul>
        </section>

        <section>
          <h2 class="text-xl font-semibold text-gray-900 mb-4">9. Perubahan Kebijakan</h2>
          <p>
            Kami dapat memperbarui Kebijakan Privasi ini dari waktu ke waktu. Perubahan signifikan akan
            diberitahukan melalui email atau notifikasi di dashboard setidaknya 14 hari sebelum berlaku.
            Penggunaan layanan setelah perubahan berlaku berarti Anda menyetujui kebijakan yang diperbarui.
          </p>
        </section>

        <section>
          <h2 class="text-xl font-semibold text-gray-900 mb-4">10. Hubungi Kami</h2>
          <p>Jika Anda memiliki pertanyaan tentang Kebijakan Privasi ini, silakan hubungi kami:</p>
          <div class="mt-4 bg-gray-50 border border-gray-200 rounded-xl p-6 text-sm space-y-2">
            <p><strong>FolkraCS</strong></p>
            <p>Email: <a href="mailto:privacy@folkra.co" class="text-indigo-600 hover:underline">privacy@folkra.co</a></p>
            <p>Website: <a href="https://folkra.co" class="text-indigo-600 hover:underline">https://folkra.co</a></p>
            <p>WhatsApp: <a href="https://wa.me/6281234567890" class="text-indigo-600 hover:underline">+62 812-3456-7890</a></p>
          </div>
        </section>

      </div>
    </main>

    <!-- Footer -->
    <footer class="border-t border-gray-100 mt-16 py-8 bg-gray-50">
      <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-sm text-gray-400">
        <p>© {{ new Date().getFullYear() }} FolkraCS. Hak cipta dilindungi.</p>
        <div class="flex gap-6">
          <Link href="/" class="hover:text-gray-600 transition-colors">Beranda</Link>
          <a href="#" class="hover:text-gray-600 transition-colors">Syarat & Ketentuan</a>
          <Link href="/login" class="hover:text-gray-600 transition-colors">Masuk</Link>
        </div>
      </div>
    </footer>

  </div>
</template>
