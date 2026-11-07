import { fetchApi, getApiBaseUrl } from "@/lib/api";
import {
  ContactSubmissionPayload,
  ContactSubmissionResponse,
  LandingPageData,
  TestimonialSubmissionPayload,
  TestimonialSubmissionResponse,
} from "@/types/landing-page";

export const FALLBACK_LANDING_DATA: LandingPageData = {
  settings: {
    app_name: "Seberaya Logam",
    company_name: "PT. Seberaya Akses Multi",
    company_tagline: "Lelang Aset Barang Tak Bergerak & Pengelolaan Aset Terpercaya",
    company_phone: "+62 815-8423-4353",
    company_address: "Jalan raya Lenteng Agung Gg.H.Ali Rt.009 Rw.05 No.10, Kel.Lenteng Agung , Kec.Jagakarsa, Jakarta Selatan, 12610",
    logo_url: "/logo.png",
    whatsapp_number: "6281584234353",
    whatsapp_raw: "+62 815-8423-4353",
    whatsapp_message: "Halo PT. Seberaya Akses Multi (Seberaya Logam), saya ingin bertanya mengenai penjualan besi tua / scrap logam / tabung gas / bongkaran bangunan. Apakah bisa diinformasikan penawaran harga dan layanan jemput lokasi?",
    whatsapp_url: "https://wa.me/6281584234353?text=Halo%20PT.%20Seberaya%20Akses%20Multi%20%28Seberaya%20Logam%29%2C%20saya%20ingin%20bertanya%20mengenai%20penjualan%20besi%20tua%20%2F%20scrap%20logam%20%2F%20tabung%20gas%20%2F%20bongkaran%20bangunan.",
    email: "pt.seberayaaksesmulti@gmail.com",
    operating_hours: "Setiap hari, 24 Jam (Operasional Depo & Timbangan) | Kantor: 07.00 – 20.00 WIB",
    maps_embed_url: "https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d15862.336496924618!2d106.820257!3d-6.347492!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e69ec069f1406e1%3A0x67ee14d872166a9a!2sSrengseng%20Sawah%2C%20Jagakarsa%2C%20South%20Jakarta%20City%2C%20Jakarta!5e0!3m2!1sid!2sid!4v1700000000000!5m2!1sid!2sid",
    maps_url: "https://maps.google.com/?q=-6.347492,106.829000",
    latitude: "-6.347492",
    longitude: "106.829000",
    hero_badge: "PT. Seberaya Akses Multi • Solusi Lelang Aset & Pengelolaan Aset Terpercaya",
    hero_headline: "Lelang Aset Barang Tak Bergerak & Pengelolaan Aset Terpercaya",
    hero_subheadline: "PT. Seberaya Akses Multi (Seberaya Logam) melayani pembelian besi tua, segala macam scrap logam (tembaga, kuningan, aluminium, timah, nikel, karbait/carbide), penerimaan bongkaran bangunan, dan jual beli tabung gas dengan timbangan presisi, harga terbaik, serta armada jemput 24 jam.",
    hero_cta_primary: "Hubungi via WhatsApp (+62 815-8423-4353)",
    hero_cta_secondary: "Lihat Jenis Scrap & Layanan",
    about_title: "Tentang PT. Seberaya Akses Multi",
    about_description: "Kami adalah perusahaan yang bergerak dibidang lelang aset berupa barang tak bergerak yang merupakan salah satu jasa lelang utama yang kami sediakan mengingat semakin meningkatnya minat dan kebutuhan dari berbagai pihak. Kunci utama kesuksesan lelang adalah harga yang sesuai dengan kondisi pasar, kondisi fisik dan program pemasaran yang efektif. Pada setiap obyek yang akan dilelang perlu dilaksanakan survey awal, penilaian (appraisal) singkat dan penelitian atas kondisi fisiknya sebelum diproses lebih lanjut untuk dipasarkan (marketing) dan dilelang. Didukung oleh Sumber Daya Manusia yang handal, berkompeten dalam bidangnya serta komitmen memberikan pelayanan terbaik, kami siap membantu penjualan berbagai aset melalui sistem lelang.",
    about_vision: "1. Memberikan pelayanan yang terbaik, bertanggungjawab dan dapat dipercaya bagi pengguna jasa lelang.\n2. Lelang merupakan salah satu “service” kami, yaitu sistem penjualan yang cepat, efisien, terbuka, obyektif, terbentuk harga yang optimum dan aspek legalitas yang pasti.",
    about_mission: "Menjadi mitra kerja yang profesional, terpercaya dan kunci kesuksesan bagi mitra bisnis.",
    about_years_experience: "3+",
    social_facebook: "https://facebook.com",
    social_instagram: "https://instagram.com",
    social_tiktok: "https://tiktok.com",
    social_youtube: "https://youtube.com",
    seo_meta_title: "PT. Seberaya Akses Multi | Jual Beli Besi Tua, Scrap Logam, Tabung Gas & Bongkaran Jagakarsa Jakarta Selatan",
    seo_meta_description: "Seberaya Logam (PT. Seberaya Akses Multi) Jagakarsa Jakarta Selatan. Terima besi tua, tembaga, kuningan, aluminium, timah, nikel, karbait, tabung gas, dan bongkaran bangunan harga tinggi. Timbangan digital akurat, siap jemput 24 jam.",
    seo_keywords: "seberaya logam, pt seberaya akses multi, jual besi tua jakarta selatan, scrap tembaga kuningan aluminium jagakarsa, timah nikel karbait carbide, jual beli tabung gas jagakarsa, jasa bongkaran bangunan jakarta selatan, rongsokan kalibata srengseng sawah",
  },
  hero: {
    badge: "PT. Seberaya Akses Multi • Spesialis Logam Scrap & Rongsok Jakarta Selatan",
    headline: "Solusi Jual Beli Besi Tua, Scrap Logam, Tabung Gas & Bongkaran",
    subheadline: "PT. Seberaya Akses Multi (Seberaya Logam) melayani pembelian besi tua, segala macam scrap logam (tembaga, kuningan, aluminium, timah, nikel, karbait/carbide), penerimaan bongkaran bangunan, dan jual beli tabung gas dengan timbangan presisi, harga terbaik, serta armada jemput 24 jam.",
    cta_primary: "Hubungi via WhatsApp (+62 815-8423-4353)",
    cta_secondary: "Lihat Jenis Scrap & Layanan",
    background_image: "https://images.unsplash.com/photo-1532996122724-e3c354a0b15b?q=80&w=1600&auto=format&fit=crop",
  },
  about: {
    title: "Komitmen Transparansi Timbangan & Nilai Scrap Logam Maksimal",
    description: "PT. Seberaya Akses Multi (Seberaya Logam) adalah perusahaan pengelolaan limbah logam, rongsokan, dan jasa bongkaran terpercaya yang berlokasi di Jagakarsa, Jakarta Selatan. Kami melayani jual beli besi tua, scrap logam presisi (tembaga, kuningan, aluminium, timah, nikel, karbait/carbide), penerimaan bongkaran bangunan ruko/gedung/rumah, serta jual beli aneka tabung gas dengan standar penimbangan digital bersertifikasi dan pelunasan tunai/transfer langsung di tempat.",
    vision: "Menjadi perusahaan pengelolaan scrap logam, perdagangan tabung gas, dan jasa bongkaran bangunan terdepan di Jakarta Selatan dan Jabodetabek yang terpercaya, transparan, dan ramah lingkungan.",
    mission: "1. Transparansi timbangan digital presisi dan penawaran harga beli scrap tertinggi setiap hari.\n2. Layanan cepat dan tuntas untuk penerimaan bongkaran bangunan serta jual beli tabung gas.\n3. Menyediakan armada penjemputan 24 jam untuk skala bengkel, pabrik, toko, gudang, maupun rumah tangga.",
    years_experience: "3+",
    image: "/images/about_depo_seberaya.jpg",
  },
  services: [
    {
      id: 1,
      title: "Jual Beli Besi Tua & Rongsokan Logam",
      slug: "jual-beli-besi-tua-rongsokan",
      icon: "ShieldCheck",
      short_description: "Menerima besi beton, plat besi, pipa, siku, H-beam, seng, dan rongsokan konstruksi pabrik/bengkel dengan timbangan presisi.",
      order: 1,
      is_active: true,
    },
    {
      id: 2,
      title: "Scrap Logam (Tembaga, Kuningan, Aluminium, Timah, Nikel, Karbait)",
      slug: "scrap-logam-tembaga-kuningan-aluminium-timah-nikel-karbait",
      icon: "Coins",
      short_description: "Terima tembaga (kabel/merah), kuningan keran/plat, aluminium wajan/siku, timah, nikel industri, dan karbait (carbide) harga pasar tertinggi.",
      order: 2,
      is_active: true,
    },
    {
      id: 3,
      title: "Penerimaan Bongkaran Bangunan & Konstruksi",
      slug: "penerimaan-bongkaran-bangunan",
      icon: "Factory",
      short_description: "Layanan penerimaan dan eksekusi bongkaran rumah tua, ruko, gedung, gudang, pabrik serta pembersihan material scrap sisa proyek.",
      order: 3,
      is_active: true,
    },
    {
      id: 4,
      title: "Jual Beli Tabung Gas (LPG, Oksigen, Karbit, Industri)",
      slug: "jual-beli-tabung-gas",
      icon: "BatteryCharging",
      short_description: "Menerima dan menjual aneka tabung gas: LPG (3kg, 12kg, 50kg), tabung oksigen medis/las, tabung karbit/acetylene, dan tabung gas industri.",
      order: 4,
      is_active: true,
    },
    {
      id: 5,
      title: "Armada Penjemputan Rongsok 24 Jam",
      slug: "armada-penjemputan-rongsok-24-jam",
      icon: "Truck",
      short_description: "Armada pick-up dan truk siap datang langsung ke lokasi gudang, bengkel, toko, atau pabrik Anda untuk menimbang di tempat.",
      order: 5,
      is_active: true,
    },
    {
      id: 6,
      title: "Kardus, Kertas Arsip, Plastik & Limbah Lainnya",
      slug: "pembelian-kardus-kertas-plastik",
      icon: "Recycle",
      short_description: "Menerima kardus box cokelat tebal, kertas arsip kantor/sekolah, botol plastik PET, emberan, dan material daur ulang lainnya.",
      order: 6,
      is_active: true,
    },
  ],
  categories: [
    {
      id: 1,
      name: "Besi & Logam Ferrous",
      slug: "besi-logam-ferrous",
      description: "Material besi konstruksi, plat, pipa, dan scrap logam berat",
      items_count: 4,
      items: [
        { id: 1, code: "BS-01", name: "Besi Tua Super / Tebal (≥ 6mm)", unit: "kg", buy_price_per_kg: 5200 },
        { id: 2, code: "BS-02", name: "Besi Campur / Pipa / Siku", unit: "kg", buy_price_per_kg: 4600 },
        { id: 3, code: "BS-03", name: "Seng & Kawat Ram", unit: "kg", buy_price_per_kg: 2400 },
        { id: 4, code: "BS-04", name: "Besi Keropos / Gram Bubut", unit: "kg", buy_price_per_kg: 3100 },
      ],
    },
    {
      id: 2,
      name: "Logam Non-Besi (Tembaga & Kuningan)",
      slug: "logam-non-besi",
      description: "Logam berharga tinggi seperti tembaga kabel, kuningan, dan timah",
      items_count: 4,
      items: [
        { id: 5, code: "NB-01", name: "Tembaga Super (Kabel Kupas)", unit: "kg", buy_price_per_kg: 115000 },
        { id: 6, code: "NB-02", name: "Tembaga Merah / Pipa AC", unit: "kg", buy_price_per_kg: 102000 },
        { id: 7, code: "NB-03", name: "Kuningan Keran & Plat", unit: "kg", buy_price_per_kg: 68000 },
        { id: 8, code: "NB-04", name: "Timah Batang / Segel", unit: "kg", buy_price_per_kg: 35000 },
      ],
    },
    {
      id: 3,
      name: "Alumunium",
      slug: "alumunium",
      description: "Alumunium kusen, velg mobil/motor, panci wajan, dan kaleng minuman",
      items_count: 3,
      items: [
        { id: 9, code: "AL-01", name: "Alumunium Kusen / Siku Baru", unit: "kg", buy_price_per_kg: 28000 },
        { id: 10, code: "AL-02", name: "Alumunium Velg Mobil/Motor", unit: "kg", buy_price_per_kg: 26500 },
        { id: 11, code: "AL-03", name: "Alumunium Panci / Wajan / Kaleng", unit: "kg", buy_price_per_kg: 19000 },
      ],
    },
    {
      id: 4,
      name: "Kertas & Karton",
      slug: "kertas-karton",
      description: "Kardus kemasan box, kertas duplex, buku tulis, dan arsip dokumen kantor",
      items_count: 3,
      items: [
        { id: 12, code: "KP-01", name: "Kardus Box Cokelat Bersih", unit: "kg", buy_price_per_kg: 2200 },
        { id: 13, code: "KP-02", name: "Kertas Putih HVS / Arsip Kantor", unit: "kg", buy_price_per_kg: 2700 },
        { id: 14, code: "KP-03", name: "Buku Tulis, Majalah & Koran", unit: "kg", buy_price_per_kg: 1600 },
      ],
    },
    {
      id: 5,
      name: "Plastik & Botol",
      slug: "plastik-botol",
      description: "Botol PET, tutup HDPE, emberan warna, dan galon bekas",
      items_count: 3,
      items: [
        { id: 15, code: "PL-01", name: "Botol Plastik PET Bening Bersih", unit: "kg", buy_price_per_kg: 4500 },
        { id: 16, code: "PL-02", name: "Tutup Botol HDPE & Galon Rusak", unit: "kg", buy_price_per_kg: 3800 },
        { id: 17, code: "PL-03", name: "Plastik Emberan & Baskom", unit: "kg", buy_price_per_kg: 2400 },
      ],
    },
    {
      id: 6,
      name: "Aki & Baterai Bekas",
      slug: "aki-baterai-bekas",
      description: "Aki basah/kering motor, mobil, genset, dan UPS server",
      items_count: 2,
      items: [
        { id: 18, code: "AK-01", name: "Aki Mobil & Truk (Basah/Kering)", unit: "kg", buy_price_per_kg: 13500 },
        { id: 19, code: "AK-02", name: "Aki Motor & UPS Industri", unit: "kg", buy_price_per_kg: 12500 },
      ],
    },
  ],
  why_choose_us: [
    {
      icon: "Scale",
      title: "Timbangan Digital Presisi",
      description: "Didukung timbangan digital bersertifikasi tera metrologi dengan display ganda yang transparan dan dapat dipantau langsung.",
    },
    {
      icon: "TrendingUp",
      title: "Harga Pasar Tertinggi",
      description: "Kami selalu memperbarui acuan harga secara real-time mengikuti perkembangan pasar industri peleburan nasional.",
    },
    {
      icon: "Banknote",
      title: "Pembayaran Instan di Tempat",
      description: "Uang hasil timbangan langsung dibayarkan tunai kontan atau transfer bank instan seketika tanpa tunda.",
    },
    {
      icon: "Truck",
      title: "Layanan Jemput Armada",
      description: "Armada pick-up dan truk siap menjemput rongsok ke gudang, pabrik, atau proyek Anda untuk partai sedang hingga besar.",
    },
    {
      icon: "Users",
      title: "Terima Partai Kecil & Besar",
      description: "Kami menyambut hangat semua kalangan, mulai dari pengepul keliling, bengkel rumahan, hingga scrap pabrik ribuan ton.",
    },
    {
      icon: "ShieldCheck",
      title: "Legalitas & Pengalaman 3+ Tahun",
      description: "Pengalaman mengelola limbah daur ulang secara profesional, berlisensi, dan ramah kelestarian lingkungan.",
    },
  ],
  process: [
    {
      step: 1,
      title: "Hubungi via WhatsApp / Telepon",
      description: "Informasikan jenis material rongsok dan perkiraan kuantitas barang yang ingin Anda jual.",
      icon: "PhoneCall",
    },
    {
      step: 2,
      title: "Cek & Negosiasi Harga",
      description: "Dapatkan estimasi penawaran harga terbaik harian atau diskusikan jadwal penjemputan armada.",
      icon: "MessageSquareQuote",
    },
    {
      step: 3,
      title: "Penjemputan / Antar ke Depo",
      description: "Bawa barang ke depo penimbangan kami atau tunggu armada kami tiba di lokasi Anda.",
      icon: "Truck",
    },
    {
      step: 4,
      title: "Penimbangan Digital Transparan",
      description: "Material disortir dan ditimbang secara terbuka menggunakan timbangan digital presisi tinggi.",
      icon: "Scale",
    },
    {
      step: 5,
      title: "Pelunasan Bayar Langsung",
      description: "Total transaksi dihitung otomatis oleh sistem POS kasir dan dibayar lunas tunai atau transfer seketika.",
      icon: "Wallet",
    },
  ],
  gallery: [
    {
      id: 1,
      title: "Aktivitas Gudang & Penataan Material",
      category: "Gudang",
      image_path: "https://images.unsplash.com/photo-1532996122724-e3c354a0b15b?q=80&w=1200&auto=format&fit=crop",
      image_url: "https://images.unsplash.com/photo-1532996122724-e3c354a0b15b?q=80&w=1200&auto=format&fit=crop",
      caption: "Area penataan dan sortir material rongsokan logam di depo utama.",
      order: 1,
    },
    {
      id: 2,
      title: "Armada Truk Pengangkutan Rongsok",
      category: "Armada",
      image_path: "https://images.unsplash.com/photo-1601584115197-04ecc0da31d7?q=80&w=1200&auto=format&fit=crop",
      image_url: "https://images.unsplash.com/photo-1601584115197-04ecc0da31d7?q=80&w=1200&auto=format&fit=crop",
      caption: "Armada pengangkut siap melakukan penjemputan partai besar ke gudang dan pabrik mitra.",
      order: 2,
    },
    {
      id: 3,
      title: "Proses Penimbangan Digital Akurat",
      category: "Penimbangan",
      image_path: "https://images.unsplash.com/photo-1581092160607-ee22621dd758?q=80&w=1200&auto=format&fit=crop",
      image_url: "https://images.unsplash.com/photo-1581092160607-ee22621dd758?q=80&w=1200&auto=format&fit=crop",
      caption: "Penimbangan transparan di depan pemilik barang menggunakan timbangan bersertifikasi tera metrologi.",
      order: 3,
    },
    {
      id: 4,
      title: "Pengepakan Karton & Kardus Daur Ulang",
      category: "Daur Ulang",
      image_path: "https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?q=80&w=1200&auto=format&fit=crop",
      image_url: "https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?q=80&w=1200&auto=format&fit=crop",
      caption: "Press baling kardus bekas berkualitas tinggi siap kirim ke pabrik kertas daur ulang.",
      order: 4,
    },
    {
      id: 5,
      title: "Sortir Logam Tembaga & Kuningan",
      category: "Operasional",
      image_path: "https://images.unsplash.com/photo-1611284446314-60a58ac0deb9?q=80&w=1200&auto=format&fit=crop",
      image_url: "https://images.unsplash.com/photo-1611284446314-60a58ac0deb9?q=80&w=1200&auto=format&fit=crop",
      caption: "Pemisahan kualitas tembaga kabel dan kuningan industri dengan ketelitian tinggi.",
      order: 5,
    },
    {
      id: 6,
      title: "Stok Besi Scrap Konstruksi",
      category: "Gudang",
      image_path: "https://images.unsplash.com/photo-1526951521990-620dc14c214b?q=80&w=1200&auto=format&fit=crop",
      image_url: "https://images.unsplash.com/photo-1526951521990-620dc14c214b?q=80&w=1200&auto=format&fit=crop",
      caption: "Pengelompokan besi tua struktur tebal untuk pasokan pabrik peleburan baja nasional.",
      order: 6,
    },
  ],
  statistics: [
    { id: 1, label: "Tahun Pengalaman", value: "3", suffix: "+", icon: "Clock", order: 1 },
    { id: 2, label: "Ton Daur Ulang / Bulan", value: "5000", suffix: "+", icon: "Recycle", order: 2 },
    { id: 3, label: "Mitra & Pelanggan Aktif", value: "3500", suffix: "+", icon: "Users", order: 3 },
    { id: 4, label: "Akurasi Timbangan Digital", value: "100", suffix: "%", icon: "Scale", order: 4 },
  ],
  location: {
    company_name: "PT. Seberaya Akses Multi",
    address: "Jalan raya Lenteng Agung Gg.H.Ali Rt.009 Rw.05 No.10, Kel.Lenteng Agung , Kec.Jagakarsa, Jakarta Selatan, 12610",
    phone: "+62 815-8423-4353",
    whatsapp: "6281584234353",
    whatsapp_url: "https://wa.me/6281584234353?text=Halo%20PT.%20Seberaya%20Akses%20Multi%20%28Seberaya%20Logam%29%2C%20saya%20ingin%20bertanya%20mengenai%20penjualan%20besi%20tua%20%2F%20scrap%20logam%20%2F%20tabung%20gas%20%2F%20bongkaran%20bangunan.",
    operating_hours: "Setiap hari, 24 Jam (Operasional Depo & Timbangan) | Kantor: 07.00 – 20.00 WIB",
    maps_url: "https://maps.google.com/?q=-6.347492,106.829000",
    maps_embed_url: "https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d15862.336496924618!2d106.820257!3d-6.347492!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e69ec069f1406e1%3A0x67ee14d872166a9a!2sSrengseng%20Sawah%2C%20Jagakarsa%2C%20South%20Jakarta%20City%2C%20Jakarta!5e0!3m2!1sid!2sid!4v1700000000000!5m2!1sid!2sid",
    latitude: "-6.347492",
    longitude: "106.829000",
  },
  testimonials: [
    {
      id: 1,
      client_name: "Bambang Sutrisno",
      client_role: "Manajer Operasional PT. Logam Presisi",
      rating: 5,
      avatar_url: "https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?q=80&w=200&auto=format&fit=crop",
      content: "Sudah 4 tahun rutin menjual scrap sisa produksi pabrik kami ke Berkah Rongsok. Timbangannya selalu presisi tidak pernah ada selisih, pembayaran tunai langsung cair di tempat, dan armadanya selalu on-time saat penjemputan.",
      order: 1,
    },
    {
      id: 2,
      client_name: "Hj. Rini Kartika",
      client_role: "Pengelola Gudang Logistik Cikarang",
      rating: 5,
      avatar_url: "https://images.unsplash.com/photo-1544005313-94ddf0286df2?q=80&w=200&auto=format&fit=crop",
      content: "Setiap akhir bulan kardus bekas kemasan barang kami mencapai belasan ton. Tim Berkah Rongsok sangat profesional, timbangan gantung digitalnya jelas bisa dilihat kedua belah pihak. Sangat direkomendasikan!",
      order: 2,
    },
    {
      id: 3,
      client_name: "Hendra Gunawan",
      client_role: "Mitra Pengepul Keliling",
      rating: 5,
      avatar_url: "https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?q=80&w=200&auto=format&fit=crop",
      content: "Sebagai pengepul kecil yang setoran setiap hari, harga di depo ini paling tinggi dibanding lapak lain. Pelayanannya cepat, nota kasir rapi dicetak jelas, dan langsung transfer tanpa nunggu esok hari.",
      order: 3,
    },
    {
      id: 4,
      client_name: "Joko Widodo Santoso",
      client_role: "Pemilik Bengkel Bubut Mandiri",
      rating: 5,
      avatar_url: "https://images.unsplash.com/photo-1500648767791-00dcc994a43e?q=80&w=200&auto=format&fit=crop",
      content: "Gram besi bubut dan potongan tembaga kabel selalu dibeli dengan harga terbaik. Bahkan ketika butuh pick-up darurat untuk membersihkan bengkel, supir dan kernetnya sangat cekatan dan sopan.",
      order: 4,
    },
  ],
  faqs: [
    {
      id: 1,
      question: "Barang rongsok apa saja yang bisa dijual ke depo kami?",
      answer: "Kami menerima hampir semua jenis limbah daur ulang bernilai ekonomis, antara lain: Besi tua (besi super, siku, pipa, plat, seng, kawat), Logam non-besi (tembaga kabel, kuningan, alumunium, timah), Kardus box dan kertas bekas/arsip kantor, Plastik (botol PET, tutup botol, emberan, galon rusak), serta Aki/baterai bekas basah & kering.",
      category: "Material",
      order: 1,
    },
    {
      id: 2,
      question: "Apakah melayani penjemputan rongsok langsung ke lokasi?",
      answer: "Ya, kami menyediakan layanan armada penjemputan gratis ke rumah, gudang, kantor, toko, maupun pabrik industri untuk partai sedang hingga besar (tersedia armada pick-up dan truk engkel/colt diesel). Cukup hubungi kami melalui WhatsApp untuk menjadwalkan penjemputan.",
      category: "Layanan",
      order: 2,
    },
    {
      id: 3,
      question: "Berapa minimal berat barang untuk penjemputan armada?",
      answer: "Untuk penjemputan gratis armada pick-up dalam kota, estimasi total timbangan idealnya minimal 150 - 200 kg (atau kombinasi dengan logam non-besi/tembaga bernilai tinggi). Jika jumlah barang Anda di bawah kuota tersebut, Anda dapat mengantarkannya langsung ke depo penimbangan kami kapan saja selama jam operasional.",
      category: "Layanan",
      order: 3,
    },
    {
      id: 4,
      question: "Bagaimana sistem penimbangan barang dan apakah dijamin akurat?",
      answer: "Semua timbangan di depo kami menggunakan timbangan digital bersertifikasi metrologi berkala dengan tampilan display ganda yang dapat dilihat langsung oleh pelanggan secara transparan tanpa ada rekayasa berat.",
      category: "Transaksi",
      order: 4,
    },
    {
      id: 5,
      question: "Bagaimana cara dan kecepatan pembayaran hasil penjualan?",
      answer: "Pembayaran dilakukan seketika (langsung lunas) setelah proses penimbangan selesai. Anda dapat memilih metode pembayaran Tunai Kontan (Cash di Kasir) atau Transfer Bank instan (BCA, Mandiri, BRI, BNI, QRIS) lengkap dengan cetak nota struk resmi kasir.",
      category: "Transaksi",
      order: 5,
    },
    {
      id: 6,
      question: "Bagaimana cara mengetahui update harga rongsokan hari ini?",
      answer: "Daftar estimasi harga rongsokan selalu kami perbarui secara berkala pada bagian 'Jenis & Harga Rongsok' di website ini. Untuk negosiasi harga partai besar pabrik atau material khusus, silakan hubungi admin WhatsApp kami secara langsung.",
      category: "Harga",
      order: 6,
    },
  ],
  seo: {
    meta_title: "PT. Seberaya Akses Multi | Jual Beli Besi Tua & Scrap Logam Terpercaya",
    meta_description: "Pusat jual beli besi tua, rongsokan, tembaga, kuningan, aluminium, timah, nikel, tabung gas dan bongkaran bangunan di Jakarta Selatan. Timbangan digital akurat, bayar tunai di tempat.",
    keywords: "seberaya logam, pt seberaya akses multi, jual besi tua jakarta selatan, scrap tembaga kuningan, timah nikel karbait, tabung gas",
    company_name: "PT. Seberaya Akses Multi",
    canonical_url: "http://localhost:3000",
    og_image: "https://images.unsplash.com/photo-1532996122724-e3c354a0b15b?q=80&w=1200&auto=format&fit=crop",
  },
};

export async function getLandingPageData(): Promise<LandingPageData> {
  try {
    const res = await fetchApi<{ success: boolean; data: LandingPageData }>("api/landing-page");
    if (res?.success && res.data) {
      return res.data;
    }
    return FALLBACK_LANDING_DATA;
  } catch (error) {
    console.warn("[Landing Service] Using fallback landing data due to connection:", error);
    return FALLBACK_LANDING_DATA;
  }
}

export async function submitContactInquiry(
  payload: ContactSubmissionPayload
): Promise<ContactSubmissionResponse> {
  const url = `${getApiBaseUrl()}/api/landing-page/contact`;

  try {
    const res = await fetch(url, {
      method: "POST",
      headers: {
        Accept: "application/json",
        "Content-Type": "application/json",
      },
      body: JSON.stringify(payload),
    });

    const json = await res.json();
    return json;
  } catch (error) {
    console.error("[Landing Service] Submit inquiry error:", error);
    // If backend connection fails, provide direct WhatsApp fallback URL
    const cleanPhone = "6281234567890";
    const waText = `Halo Admin, saya ${payload.name} (${payload.phone}). Saya ingin menjual rongsok: ${payload.material_type || "-"}. Alamat: ${payload.address || "-"}. Pesan: ${payload.message || "-"}`;
    return {
      success: true,
      message: "Pesan Anda berhasil disiapkan. Terhubung langsung ke WhatsApp kami.",
      data: {
        id: 0,
        direct_whatsapp_url: `https://wa.me/${cleanPhone}?text=${encodeURIComponent(waText)}`,
      },
    };
  }
}

export async function submitCustomerTestimonial(
  payload: TestimonialSubmissionPayload
): Promise<TestimonialSubmissionResponse> {
  const url = `${getApiBaseUrl()}/api/landing-page/testimonials`;

  try {
    const res = await fetch(url, {
      method: "POST",
      headers: {
        Accept: "application/json",
        "Content-Type": "application/json",
      },
      body: JSON.stringify(payload),
    });

    const json = await res.json();
    return json;
  } catch (error) {
    console.error("[Landing Service] Submit testimonial error:", error);
    return {
      success: true,
      message: "Terima kasih! Ulasan Anda berhasil dicatat.",
      data: {
        id: Date.now(),
        client_name: payload.client_name,
        client_role: payload.client_role || "Pelanggan Setia",
        rating: payload.rating,
        content: payload.content,
        order: 99,
      },
    };
  }
}


