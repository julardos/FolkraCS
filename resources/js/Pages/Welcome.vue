<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import { MessageSquare, Bot, Zap, Shield, Users, BarChart3, ChevronDown, ChevronUp, Check, ArrowRight, Instagram, Phone, Star } from 'lucide-vue-next';

const mobileMenuOpen = ref(false);
const openFaq = ref(null);

const faqs = [
  {
    q: 'Apakah saya perlu keahlian teknis untuk menggunakannya?',
    a: 'Tidak. FolkraCS dirancang agar mudah digunakan oleh siapa saja. Cukup hubungkan WhatsApp, atur instruksi AI, dan bot Anda siap melayani pelanggan.'
  },
  {
    q: 'Berapa lama setup awal memakan waktu?',
    a: 'Rata-rata 15–30 menit. Kami menyediakan panduan langkah demi langkah mulai dari menghubungkan WhatsApp hingga mengkonfigurasi instruksi AI sesuai bisnis Anda.'
  },
  {
    q: 'Apakah AI bisa memahami bahasa Indonesia dengan baik?',
    a: 'Ya. FolkraCS menggunakan model AI terbaru yang sangat baik dalam memahami dan merespons dalam Bahasa Indonesia, termasuk bahasa gaul dan variasi regional.'
  },
  {
    q: 'Bisakah saya mengambil alih percakapan dari AI?',
    a: 'Tentu. Fitur Live Agent memungkinkan tim Anda mengambil alih percakapan kapan saja dengan satu klik, lalu menyerahkan kembali ke AI bila sudah selesai.'
  },
  {
    q: 'Apakah ada masa percobaan gratis?',
    a: 'Ya, setiap paket mendapatkan masa percobaan 14 hari tanpa perlu kartu kredit. Anda bisa upgrade atau batalkan kapan saja.'
  },
];
</script>

<template>
  <Head title="FolkraCS — AI Customer Service untuk Bisnis Indonesia" />

  <div class="bg-white font-sans antialiased text-gray-900">

    <!-- ── Navbar ──────────────────────────────────────────────── -->
    <header class="sticky top-0 z-50 bg-white/95 backdrop-blur border-b border-gray-100">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
          <a href="/" class="flex items-center">
            <img src="/logo/logo_dark.jpg" alt="FolkraCS" class="h-8 w-auto" />
          </a>

          <nav class="hidden md:flex items-center gap-8 text-sm font-medium text-gray-600">
            <a href="#fitur" class="hover:text-gray-900 transition-colors">Fitur</a>
            <a href="#cara-kerja" class="hover:text-gray-900 transition-colors">Cara Kerja</a>
            <a href="#harga" class="hover:text-gray-900 transition-colors">Harga</a>
            <a href="#faq" class="hover:text-gray-900 transition-colors">FAQ</a>
          </nav>

          <div class="hidden md:flex items-center gap-3">
            <Link href="/login" class="text-sm font-medium text-gray-600 hover:text-gray-900 px-4 py-2 transition-colors">
              Masuk
            </Link>
            <a href="#harga" class="text-sm font-semibold bg-indigo-600 text-white px-5 py-2.5 rounded-lg hover:bg-indigo-700 transition-colors">
              Mulai Gratis
            </a>
          </div>

          <button class="md:hidden p-2" @click="mobileMenuOpen = !mobileMenuOpen">
            <div class="w-5 h-0.5 bg-gray-700 mb-1"></div>
            <div class="w-5 h-0.5 bg-gray-700 mb-1"></div>
            <div class="w-5 h-0.5 bg-gray-700"></div>
          </button>
        </div>

        <!-- Mobile menu -->
        <div v-if="mobileMenuOpen" class="md:hidden py-4 border-t border-gray-100 space-y-3 text-sm font-medium">
          <a href="#fitur" class="block py-2 text-gray-600 hover:text-gray-900">Fitur</a>
          <a href="#cara-kerja" class="block py-2 text-gray-600 hover:text-gray-900">Cara Kerja</a>
          <a href="#harga" class="block py-2 text-gray-600 hover:text-gray-900">Harga</a>
          <a href="#faq" class="block py-2 text-gray-600 hover:text-gray-900">FAQ</a>
          <Link href="/login" class="block py-2 text-gray-600 hover:text-gray-900">Masuk</Link>
          <a href="#harga" class="block text-center bg-indigo-600 text-white px-5 py-2.5 rounded-lg font-semibold">Mulai Gratis</a>
        </div>
      </div>
    </header>

    <!-- ── Hero ───────────────────────────────────────────────── -->
    <section class="relative overflow-hidden bg-gradient-to-br from-indigo-950 via-indigo-900 to-indigo-800 text-white">
      <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(circle at 2px 2px, white 1px, transparent 0); background-size: 40px 40px;"></div>
      <div class="absolute top-0 right-0 w-96 h-96 bg-indigo-500 rounded-full opacity-10 blur-3xl -translate-y-1/2 translate-x-1/2"></div>
      <div class="absolute bottom-0 left-0 w-80 h-80 bg-purple-500 rounded-full opacity-10 blur-3xl translate-y-1/2 -translate-x-1/2"></div>

      <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 lg:py-32 text-center">
        <div class="inline-flex items-center gap-2 bg-indigo-800/50 border border-indigo-600/50 rounded-full px-4 py-1.5 text-sm text-indigo-200 mb-8">
          <Zap class="w-3.5 h-3.5 text-yellow-400" />
          Didukung AI Terbaru — GPT-4o, Claude, Gemini
        </div>

        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold tracking-tight leading-tight mb-6">
          CS Otomatis 24/7 untuk<br />
          <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-300 to-purple-300">Bisnis Indonesia</span>
        </h1>

        <p class="text-lg sm:text-xl text-indigo-200 max-w-2xl mx-auto mb-10 leading-relaxed">
          Hubungkan WhatsApp dan Instagram bisnis Anda ke AI. Balas pertanyaan pelanggan, terima pesanan, dan eskalasi ke tim — otomatis, kapanpun.
        </p>

        <div class="flex flex-col sm:flex-row gap-4 justify-center mb-14">
          <a href="#harga" class="inline-flex items-center justify-center gap-2 bg-white text-indigo-900 font-semibold px-8 py-4 rounded-xl hover:bg-indigo-50 transition-colors text-base shadow-lg shadow-black/20">
            Coba Gratis 14 Hari
            <ArrowRight class="w-4 h-4" />
          </a>
          <a href="#cara-kerja" class="inline-flex items-center justify-center gap-2 border border-indigo-600/50 text-white font-medium px-8 py-4 rounded-xl hover:bg-indigo-800/50 transition-colors text-base">
            Lihat Cara Kerjanya
          </a>
        </div>

        <!-- Stats bar -->
        <div class="grid grid-cols-3 gap-8 max-w-lg mx-auto pt-10 border-t border-indigo-700/50">
          <div>
            <div class="text-3xl font-bold">24/7</div>
            <div class="text-sm text-indigo-300 mt-1">Respons otomatis</div>
          </div>
          <div>
            <div class="text-3xl font-bold">&lt;3s</div>
            <div class="text-sm text-indigo-300 mt-1">Waktu balas</div>
          </div>
          <div>
            <div class="text-3xl font-bold">90%</div>
            <div class="text-sm text-indigo-300 mt-1">Pertanyaan terjawab</div>
          </div>
        </div>
      </div>
    </section>

    <!-- ── Channel badges ─────────────────────────────────────── -->
    <section class="border-b border-gray-100 bg-gray-50">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="flex flex-wrap items-center justify-center gap-6 text-sm text-gray-500">
          <span class="font-medium text-gray-400">Terhubung dengan:</span>
          <div class="flex items-center gap-2 font-semibold text-green-700 bg-green-50 border border-green-200 px-4 py-2 rounded-full">
            <Phone class="w-4 h-4" /> WhatsApp
          </div>
          <div class="flex items-center gap-2 font-semibold text-pink-700 bg-pink-50 border border-pink-200 px-4 py-2 rounded-full">
            <Instagram class="w-4 h-4" /> Instagram DM
          </div>
          <div class="flex items-center gap-2 font-semibold text-blue-700 bg-blue-50 border border-blue-200 px-4 py-2 rounded-full">
            <Bot class="w-4 h-4" /> AI Generatif
          </div>
        </div>
      </div>
    </section>

    <!-- ── Fitur ──────────────────────────────────────────────── -->
    <section id="fitur" class="py-24 bg-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
          <div class="text-sm font-semibold text-indigo-600 uppercase tracking-widest mb-3">Fitur Lengkap</div>
          <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-4">Semua yang Dibutuhkan Tim CS Anda</h2>
          <p class="text-lg text-gray-500 max-w-2xl mx-auto">Dari otomasi percakapan hingga manajemen eskalasi — satu platform untuk seluruh kebutuhan customer service.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
          <div v-for="feature in [
            { icon: Bot, color: 'indigo', title: 'AI Agent Cerdas', desc: 'Ditenagai model AI terbaru. Memahami konteks percakapan, menjawab dalam Bahasa Indonesia dengan natural, dan belajar dari knowledge base bisnis Anda.' },
            { icon: MessageSquare, color: 'green', title: 'WhatsApp & Instagram', desc: 'Kelola pesan dari WhatsApp dan Instagram DM dalam satu dashboard. Pelanggan direspons otomatis tanpa harus membuka dua aplikasi.' },
            { icon: Users, color: 'purple', title: 'Live Agent Takeover', desc: 'Tim CS Anda bisa mengambil alih percakapan kapan saja dengan satu klik. Serahkan kembali ke AI setelah selesai.' },
            { icon: Shield, color: 'orange', title: 'Knowledge Base', desc: 'Upload dokumen, FAQ, dan katalog produk. AI akan menjawab berdasarkan informasi bisnis Anda — bukan jawaban generik.' },
            { icon: Zap, color: 'yellow', title: 'Deteksi Eskalasi', desc: 'AI mendeteksi otomatis ketika ada keluhan atau masalah serius, lalu mengirim notifikasi ke tim Anda via WhatsApp atau email.' },
            { icon: BarChart3, color: 'blue', title: 'Multi-Tenant SaaS', desc: 'Satu platform untuk banyak klien. Setiap bisnis memiliki konfigurasi, AI instruction, dan knowledge base yang terpisah.' },
          ]" :key="feature.title"
          class="group p-8 rounded-2xl border border-gray-100 hover:border-indigo-200 hover:shadow-lg hover:shadow-indigo-50 transition-all duration-300">
            <div :class="`w-12 h-12 rounded-xl flex items-center justify-center mb-5 bg-${feature.color}-50`">
              <component :is="feature.icon" :class="`w-6 h-6 text-${feature.color}-600`" />
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ feature.title }}</h3>
            <p class="text-gray-500 leading-relaxed text-sm">{{ feature.desc }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- ── Cara Kerja ─────────────────────────────────────────── -->
    <section id="cara-kerja" class="py-24 bg-gray-50">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
          <div class="text-sm font-semibold text-indigo-600 uppercase tracking-widest mb-3">Cara Kerja</div>
          <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-4">Aktif dalam 3 Langkah</h2>
          <p class="text-lg text-gray-500 max-w-xl mx-auto">Tidak perlu coding. Setup dalam 30 menit, AI langsung siap melayani pelanggan Anda.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 relative">
          <div class="hidden md:block absolute top-10 left-1/3 right-1/3 h-0.5 bg-indigo-100"></div>

          <div v-for="(step, i) in [
            { n: '01', title: 'Hubungkan Channel', desc: 'Scan QR WhatsApp atau otorisasi Instagram bisnis Anda. Selesai dalam hitungan menit tanpa konfigurasi teknis.' },
            { n: '02', title: 'Atur Instruksi AI', desc: 'Tulis instruksi untuk AI: nama bot, produk/layanan, cara merespons, dan upload knowledge base bisnis Anda.' },
            { n: '03', title: 'AI Langsung Bekerja', desc: 'AI mulai merespons pesan pelanggan otomatis 24/7. Tim Anda fokus pada hal yang benar-benar memerlukan sentuhan manusia.' },
          ]" :key="step.n"
          class="relative bg-white rounded-2xl p-8 border border-gray-100 shadow-sm text-center">
            <div class="w-12 h-12 bg-indigo-600 text-white rounded-full flex items-center justify-center text-lg font-bold mx-auto mb-5">{{ step.n }}</div>
            <h3 class="text-xl font-semibold text-gray-900 mb-3">{{ step.title }}</h3>
            <p class="text-gray-500 leading-relaxed">{{ step.desc }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- ── Pricing ────────────────────────────────────────────── -->
    <section id="harga" class="py-24 bg-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
          <div class="text-sm font-semibold text-indigo-600 uppercase tracking-widest mb-3">Harga Transparan</div>
          <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-4">Pilih Paket yang Tepat</h2>
          <p class="text-lg text-gray-500 max-w-xl mx-auto">Semua paket termasuk uji coba 14 hari gratis. Batalkan kapan saja.</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 max-w-5xl mx-auto">

          <!-- Starter -->
          <div class="rounded-2xl border border-gray-200 p-8 flex flex-col">
            <div class="mb-6">
              <div class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-2">Starter</div>
              <div class="flex items-baseline gap-1">
                <span class="text-4xl font-bold text-gray-900">299rb</span>
                <span class="text-gray-500">/bulan</span>
              </div>
              <p class="text-sm text-gray-500 mt-2">Cocok untuk bisnis kecil yang baru mulai otomasi CS.</p>
            </div>
            <ul class="space-y-3 mb-8 flex-1 text-sm">
              <li v-for="f in ['1 channel WhatsApp','Hingga 2.000 pesan/bulan','AI Agent dengan instruksi custom','Knowledge base (upload dokumen)','Deteksi eskalasi otomatis','1 pengguna admin','Support via email']" :key="f" class="flex items-start gap-2.5 text-gray-600">
                <Check class="w-4 h-4 text-indigo-500 shrink-0 mt-0.5" />
                {{ f }}
              </li>
            </ul>
            <a href="/login" class="block text-center border-2 border-indigo-600 text-indigo-600 font-semibold py-3 rounded-xl hover:bg-indigo-50 transition-colors">
              Mulai Gratis
            </a>
          </div>

          <!-- Pro (highlighted) -->
          <div class="rounded-2xl border-2 border-indigo-600 p-8 flex flex-col relative shadow-xl shadow-indigo-100">
            <div class="absolute -top-4 left-1/2 -translate-x-1/2">
              <span class="bg-indigo-600 text-white text-xs font-bold px-4 py-1.5 rounded-full">PALING POPULER</span>
            </div>
            <div class="mb-6">
              <div class="text-sm font-semibold text-indigo-600 uppercase tracking-wide mb-2">Pro</div>
              <div class="flex items-baseline gap-1">
                <span class="text-4xl font-bold text-gray-900">699rb</span>
                <span class="text-gray-500">/bulan</span>
              </div>
              <p class="text-sm text-gray-500 mt-2">Untuk bisnis yang butuh WhatsApp dan Instagram sekaligus.</p>
            </div>
            <ul class="space-y-3 mb-8 flex-1 text-sm">
              <li v-for="f in ['WhatsApp + Instagram DM','Hingga 10.000 pesan/bulan','AI Agent dengan instruksi custom','Knowledge base unlimited','Deteksi eskalasi & notifikasi WA','Live Agent takeover','Hingga 5 pengguna admin','Laporan percakapan','Support prioritas']" :key="f" class="flex items-start gap-2.5 text-gray-600">
                <Check class="w-4 h-4 text-indigo-500 shrink-0 mt-0.5" />
                {{ f }}
              </li>
            </ul>
            <a href="/login" class="block text-center bg-indigo-600 text-white font-semibold py-3 rounded-xl hover:bg-indigo-700 transition-colors">
              Mulai Gratis
            </a>
          </div>

          <!-- Enterprise -->
          <div class="rounded-2xl border border-gray-200 p-8 flex flex-col bg-gray-900 text-white">
            <div class="mb-6">
              <div class="text-sm font-semibold text-gray-400 uppercase tracking-wide mb-2">Enterprise</div>
              <div class="flex items-baseline gap-1">
                <span class="text-4xl font-bold">Custom</span>
              </div>
              <p class="text-sm text-gray-400 mt-2">Untuk perusahaan dengan kebutuhan skala besar dan integrasi khusus.</p>
            </div>
            <ul class="space-y-3 mb-8 flex-1 text-sm">
              <li v-for="f in ['Channel tidak terbatas','Pesan tidak terbatas','Multi-agent & multi-session','Integrasi custom (ERP, CRM)','SLA & uptime guarantee','Dedicated account manager','On-boarding & pelatihan tim','Laporan analitik lanjutan']" :key="f" class="flex items-start gap-2.5 text-gray-400">
                <Check class="w-4 h-4 text-indigo-400 shrink-0 mt-0.5" />
                {{ f }}
              </li>
            </ul>
            <a href="https://wa.me/6281234567890" target="_blank" class="block text-center border-2 border-gray-600 text-gray-300 font-semibold py-3 rounded-xl hover:bg-gray-800 transition-colors">
              Hubungi Sales
            </a>
          </div>
        </div>

        <!-- Billing note -->
        <p class="text-center text-sm text-gray-400 mt-8">
          Harga belum termasuk PPN 11%. Pembayaran via transfer bank atau kartu kredit. Bisa dibayar tahunan dengan diskon 20%.
        </p>
      </div>
    </section>

    <!-- ── Testimonials ───────────────────────────────────────── -->
    <section class="py-24 bg-indigo-950 text-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
          <div class="text-sm font-semibold text-indigo-400 uppercase tracking-widest mb-3">Testimoni</div>
          <h2 class="text-3xl sm:text-4xl font-bold mb-4">Dipercaya Bisnis Indonesia</h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div v-for="t in [
            { name: 'Rina Santoso', role: 'Owner, Sejukin AC Service', text: 'Sebelum pakai FolkraCS, tim CS kami kewalahan balas WA dari pagi sampai malam. Sekarang AI yang handle duluan, kami cukup konfirmasi booking.' },
            { name: 'Budi Prakoso', role: 'Manager, Toko Online Fashion', text: 'Pelanggan kami suka karena respons cepat 24 jam. Omzet naik karena tidak ada lagi calon pembeli yang kabur karena nunggu lama dibalas.' },
            { name: 'Dewi Kusuma', role: 'Direktur, Klinik Kecantikan', text: 'Kami pakai fitur knowledge base untuk upload daftar treatment dan harga. AI langsung bisa jawab pertanyaan pasien dengan akurat dan profesional.' },
          ]" :key="t.name"
          class="bg-indigo-900/50 border border-indigo-800 rounded-2xl p-7">
            <div class="flex gap-1 mb-4">
              <Star v-for="i in 5" :key="i" class="w-4 h-4 text-yellow-400 fill-yellow-400" />
            </div>
            <p class="text-indigo-100 text-sm leading-relaxed mb-5">"{{ t.text }}"</p>
            <div>
              <div class="font-semibold text-white text-sm">{{ t.name }}</div>
              <div class="text-indigo-400 text-xs">{{ t.role }}</div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ── FAQ ───────────────────────────────────────────────── -->
    <section id="faq" class="py-24 bg-white">
      <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
          <div class="text-sm font-semibold text-indigo-600 uppercase tracking-widest mb-3">FAQ</div>
          <h2 class="text-3xl sm:text-4xl font-bold text-gray-900">Pertanyaan Umum</h2>
        </div>

        <div class="space-y-3">
          <div v-for="(faq, i) in faqs" :key="i"
               class="border border-gray-200 rounded-xl overflow-hidden">
            <button
              class="w-full flex items-center justify-between p-5 text-left hover:bg-gray-50 transition-colors"
              @click="openFaq = openFaq === i ? null : i"
            >
              <span class="font-medium text-gray-900 pr-4">{{ faq.q }}</span>
              <component :is="openFaq === i ? ChevronUp : ChevronDown" class="w-5 h-5 text-gray-400 shrink-0" />
            </button>
            <div v-if="openFaq === i" class="px-5 pb-5 text-sm text-gray-600 leading-relaxed border-t border-gray-100 pt-4">
              {{ faq.a }}
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ── CTA ───────────────────────────────────────────────── -->
    <section class="py-24 bg-gradient-to-br from-indigo-600 to-purple-700 text-white">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl sm:text-4xl font-bold mb-4">Siap Otomasi CS Bisnis Anda?</h2>
        <p class="text-xl text-indigo-200 mb-10">Mulai gratis 14 hari. Tanpa kartu kredit. Batalkan kapan saja.</p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
          <a href="/login" class="inline-flex items-center justify-center gap-2 bg-white text-indigo-700 font-bold px-10 py-4 rounded-xl hover:bg-indigo-50 transition-colors text-lg shadow-xl shadow-black/20">
            Coba Gratis Sekarang
            <ArrowRight class="w-5 h-5" />
          </a>
          <a href="https://wa.me/6281234567890" target="_blank" class="inline-flex items-center justify-center gap-2 border-2 border-white/40 text-white font-semibold px-10 py-4 rounded-xl hover:bg-white/10 transition-colors text-lg">
            <Phone class="w-5 h-5" />
            Tanya via WhatsApp
          </a>
        </div>
      </div>
    </section>

    <!-- ── Footer ─────────────────────────────────────────────── -->
    <footer class="bg-gray-900 text-gray-400 py-16">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-10 mb-12">
          <div class="col-span-2 md:col-span-1">
            <img src="/logo/logo_light.jpg" alt="FolkraCS" class="h-8 w-auto mb-4 brightness-0 invert" />
            <p class="text-sm leading-relaxed">Platform AI Customer Service untuk bisnis Indonesia. Otomasi WhatsApp & Instagram dengan kecerdasan buatan.</p>
          </div>
          <div>
            <h4 class="text-white font-semibold mb-4 text-sm">Produk</h4>
            <ul class="space-y-2 text-sm">
              <li><a href="#fitur" class="hover:text-white transition-colors">Fitur</a></li>
              <li><a href="#harga" class="hover:text-white transition-colors">Harga</a></li>
              <li><a href="#cara-kerja" class="hover:text-white transition-colors">Cara Kerja</a></li>
              <li><a href="#faq" class="hover:text-white transition-colors">FAQ</a></li>
            </ul>
          </div>
          <div>
            <h4 class="text-white font-semibold mb-4 text-sm">Perusahaan</h4>
            <ul class="space-y-2 text-sm">
              <li><a href="#" class="hover:text-white transition-colors">Tentang Kami</a></li>
              <li><a href="#" class="hover:text-white transition-colors">Blog</a></li>
              <li><a href="#" class="hover:text-white transition-colors">Karier</a></li>
              <li><a href="#" class="hover:text-white transition-colors">Hubungi Kami</a></li>
            </ul>
          </div>
          <div>
            <h4 class="text-white font-semibold mb-4 text-sm">Legal</h4>
            <ul class="space-y-2 text-sm">
              <li><a href="/privacy" class="hover:text-white transition-colors">Kebijakan Privasi</a></li>
              <li><a href="#" class="hover:text-white transition-colors">Syarat & Ketentuan</a></li>
              <li><a href="#" class="hover:text-white transition-colors">Kebijakan Cookie</a></li>
            </ul>
          </div>
        </div>

        <div class="border-t border-gray-800 pt-8 flex flex-col md:flex-row items-center justify-between gap-4 text-sm">
          <p>© {{ new Date().getFullYear() }} FolkraCS. Hak cipta dilindungi.</p>
          <p>Dibuat dengan ❤️ untuk bisnis Indonesia</p>
        </div>
      </div>
    </footer>

  </div>
</template>
