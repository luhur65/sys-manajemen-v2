# Laporan Keputusan — Mempercepat Single Logout

**Konteks:** sys-modern (CodeIgniter 4) sebagai aplikasi anggota SSO
**Server SSO:** auth-sso (Next.js) + auth-sso-api (NestJS)
**Tanggal:** 27 Agustus 2026 — direvisi 28 Agustus 2026 (lihat koreksi di bagian 6)
**Status:** Menunggu keputusan tim. Opsi D tampaknya sudah mulai dikerjakan —
route `sso/logout-callback` sudah ada di `app/Config/Routes.php`, tetapi method
`SsoAuth::logoutCallback` yang ditunjuknya **belum ada**, jadi rutenya masih
menghasilkan 404.

---

## 1. Keputusan yang Diminta

Single Logout sudah **berfungsi**: logout di dashboard SSO benar-benar mengakhiri
sesi sys-modern. Yang belum ideal adalah waktunya — ada jeda **sampai 60 detik**
sebelum sys-modern menyadarinya.

Pertanyaan untuk tim:

> Apakah jeda sampai 60 detik itu dapat diterima, dan kalau tidak, kita bayar
> dengan apa untuk menghilangkannya?

Dokumen ini memaparkan dari mana jeda itu berasal, empat cara menghilangkannya,
beserta biaya masing-masing. Tidak ada opsi yang gratis — yang berbeda adalah
**siapa yang membayar**: pengguna (latensi halaman), server SSO (beban request),
atau tim (pekerjaan dan risiko perubahan).

---

## 2. Dari Mana Jeda 60 Detik Itu

sys-modern memakai model **tarik (polling)**. Setiap request yang lewat
`AuthFilter` menanyakan ke auth-sso-api: apakah sesi SSO ini masih hidup?
Jawabannya disimpan di cache supaya tidak setiap halaman memicu panggilan
jaringan.

```
Request ke sys-modern
   └→ AuthFilter: sesi punya sso_sid?
        └→ SsoSlo::isSessionActive($sid)
             ├─ ada di cache?  → pakai jawaban lama          (0 ms, tanpa jaringan)
             └─ tidak ada?     → POST /auth/session/introspect (±100-300 ms)
                                 lalu simpan hasilnya di cache
```

Umur cache-nya adalah `sso.sloPollSeconds`, saat ini **60 detik**
(`app/Libraries/SsoSlo.php:73`). Jadi sesi yang dicabut tepat setelah satu
pemeriksaan baru akan ketahuan pada pemeriksaan berikutnya.

| | Nilai sekarang |
|---|---|
| Jeda terburuk | 60 detik |
| Jeda rata-rata | ±30 detik |
| Panggilan ke auth-sso-api | maksimal 1 per 60 detik per sesi aktif |
| Biaya saat cache terisi | nol — tidak ada jaringan sama sekali |

**Catatan teknis:** menurunkan `sso.sloPollSeconds` di bawah 5 tidak berpengaruh.
Ada batas bawah `max(5, ...)` di kode, sengaja dipasang supaya salah ketik
(mis. `0`) tidak berubah jadi satu panggilan jaringan pada **setiap** request.

### Kenapa jawaban negatif ikut di-cache

Sekali sesi SSO dicabut, ia tidak akan hidup lagi. Menanyakannya berulang kali
hanya menambah beban tanpa mengubah jawaban. Ini bukan sumber jeda — jeda hanya
terjadi pada jawaban positif terakhir sebelum pencabutan.

---

## 3. Opsi A — Turunkan Interval Polling

Ubah satu nilai di `.env`:

```
sso.sloPollSeconds = 5
```

**Cara kerja:** tidak ada yang berubah secara arsitektur, hanya cache-nya lebih
pendek.

**Yang didapat:** jeda turun dari 60 detik jadi maksimal 5 detik.

**Yang dibayar:** hampir setiap kali pengguna membuka halaman, sys-modern akan
menambah satu panggilan HTTPS ke auth-sso-api **sebelum halaman dirender**.
Konsekuensinya:

- Setiap halaman bertambah kira-kira **100–300 ms**, tergantung jaringan ke
  server SSO.
- auth-sso-api menjadi **dependency pada jalur render setiap halaman**. Kalau ia
  melambat, sys-modern ikut melambat. Kalau ia mati, setiap halaman menunggu
  sampai batas waktu 3 detik sebelum lanjut (sistem tetap jalan — kebijakannya
  *fail open* — tapi terasa berat).
- Beban ke auth-sso-api naik sekitar **12 kali lipat**. Dengan 30 pengguna aktif
  bersamaan: dari ±0,5 request/detik menjadi ±6 request/detik, hanya untuk
  pemeriksaan sesi.

**Pekerjaan:** satu baris `.env`. Selesai dalam hitungan menit, tidak menyentuh
repositori lain.

**Cocok bila:** tim menilai jeda beberapa detik sudah cukup, dan menerima
halaman yang sedikit lebih lambat sebagai gantinya.

---

## 4. Opsi B — Back-Channel Logout (Dorong) + Polling sebagai Jaring Pengaman

Membalik arah aliran informasinya: alih-alih sys-modern bertanya terus-menerus,
**auth-sso-api memberi tahu** saat ada sesi yang dicabut.

```
Pengguna klik "Keluar" di dashboard
   └→ auth-sso-api revokeSession(sid)
        ├─ tandai baris sso_sessions jadi revoked      (seperti sekarang)
        └─ beri tahu setiap aplikasi anggota:          (BARU)
             POST <app>/sso/backchannel-logout  { sid }
             header x-slo-secret

sys-modern menerima notifikasi
   └→ catat sid itu sebagai mati, di penyimpanan lokal
        └→ AuthFilter membaca catatan lokal itu — TANPA jaringan
             └→ klik berikutnya: sesi berakhir
```

**Yang didapat:**

- Logout terasa **instan** — sesi berakhir pada interaksi berikutnya, tanpa
  menunggu interval apa pun.
- Biaya saat normal **turun**, bukan naik: `AuthFilter` cukup membaca berkas
  lokal, tidak memanggil jaringan sama sekali selama tidak ada notifikasi.
- auth-sso-api tidak lagi berada di jalur render halaman.

**Yang dibayar:**

- Perlu **endpoint baru** di sys-modern yang menerima notifikasi, dijaga secret
  yang sama (`x-slo-secret`).
- Perlu **fan-out di auth-sso-api**, di dalam `revokeSession()`. Ini menyentuh
  jalur logout yang juga dipakai **HR dan CRM** — bagian paling sensitif dari
  perubahan ini.
- Perlu daftar alamat aplikasi anggota di konfigurasi auth-sso-api.

**Mitigasi risiko yang harus disepakati:** fan-out dibuat *best-effort* —
dibungkus try/catch, batas waktu pendek, dan **tidak boleh memblokir atau
menggagalkan logout** apa pun hasilnya. Dengan begitu, notifikasi yang gagal
terkirim ke sys-modern tidak bisa merusak logout HR atau CRM. Polling 60 detik
tetap dipertahankan sebagai jaring pengaman: kalau notifikasi hilang (sys-modern
sedang restart, jaringan putus sesaat), sesi tetap berakhir dalam satu menit —
kembali ke perilaku hari ini, bukan menjadi lebih buruk.

**Pekerjaan:** sedang. Perubahan di dua repositori:

| Repositori | Perubahan |
|---|---|
| sys-modern | 1 endpoint + 1 kelas penyimpan daftar sid tercabut + 1 cabang di `AuthFilter` + route + test |
| auth-sso-api | fan-out di `revokeSession()` (fungsinya sekarang hanya 4 baris) + daftar alamat aplikasi di `.env` |

**Cocok bila:** tim menginginkan logout yang benar-benar instan tanpa
mengorbankan kecepatan halaman, dan bersedia menyentuh `revokeSession()` dengan
pengamanan di atas.

---

## 5. Opsi C — Back-Channel Saja, Tanpa Polling

Sama seperti Opsi B, tetapi polling dimatikan sepenuhnya.

**Yang didapat:** paling hemat — nol panggilan jaringan pada kondisi normal.

**Yang dibayar:** tidak ada jaring pengaman. Kalau satu notifikasi gagal
terkirim — sys-modern sedang di-restart saat pengguna menekan Keluar, misalnya —
sesi itu **tidak akan pernah berakhir** sampai kedaluwarsa sendiri (2 jam).
Kegagalannya pun tidak terlihat oleh siapa pun.

**Rekomendasi: jangan.** Selisih biayanya dengan Opsi B sangat kecil (satu
panggilan per menit per sesi), sementara yang hilang adalah satu-satunya
mekanisme yang menangkap notifikasi yang lolos. Untuk fitur yang berkaitan
dengan keamanan, kehilangan jaring pengaman bukan penghematan yang sepadan.

---

## 6. Opsi D — Front-Channel Logout (Browser yang Mengantar)

> **Koreksi terhadap versi pertama dokumen ini.** Opsi ini semula ditulis sebagai
> "dipertimbangkan dan ditolak", dengan alasan bergantung pada cookie pihak
> ketiga yang kini diblokir Safari dan Firefox. **Alasan itu keliru untuk
> susunan domain kita.** SameSite dinilai per *registrable domain* (eTLD+1),
> bukan per origin: `testsso.transporindo.com`, `staging.transporindo.com`, dan
> `sys.transporindo.com` semuanya berada di bawah `transporindo.com`, sehingga
> request di antara mereka adalah **same-site**, bukan pihak ketiga. Cookie sesi
> sys-modern (`SameSite=Lax`, `Config\Cookie`) tetap terkirim. Opsi ini layak,
> dan dinaikkan menjadi opsi penuh.

Saat pengguna menekan Keluar, dashboard memuat url logout setiap aplikasi
sebagai subresource — `<img src="…/sso/logout-callback?sid=…">` — sehingga
**browser pengguna sendiri** yang mengantar perintah logout. Karena request itu
berasal dari browser yang memegang cookie sesi, sys-modern tahu persis sesi mana
yang dimaksud.

**Yang didapat:**

- Logout **instan**, sama seperti Opsi B.
- Tidak perlu menyentuh `revokeSession()` di auth-sso-api sama sekali — yang
  berubah hanya halaman logout dashboard. Ini menghilangkan risiko terbesar
  Opsi B.
- Tidak menambah beban jaringan pada kondisi normal.

**Yang dibayar — batasnya nyata dan harus disadari:**

- **Hanya bekerja bila pengguna sedang berada di halaman dashboard saat logout.**
  Sesi yang dicabut lewat jalur lain — admin mencabut dari belakang layar,
  logout dari perangkat lain, sesi kedaluwarsa — tidak akan memicu apa pun.
- **Fire-and-forget.** Dashboard tidak tahu apakah perintahnya sampai. Kalau
  browser memblokir gambarnya atau jaringannya putus, tidak ada yang tahu.
- **Rapuh terhadap perubahan domain.** Hari ketika satu aplikasi pindah ke
  registrable domain yang berbeda, mekanisme ini berhenti bekerja — dan
  berhentinya diam-diam, tanpa error.
- Endpoint-nya **wajib mencocokkan `sid` dengan sesi pemanggil**. Tanpa itu,
  siapa pun bisa memaksa orang lain logout hanya dengan menaruh `<img>` di
  halaman mana pun.

**Karena itu Opsi D tetap membutuhkan polling sebagai jaring pengaman,** persis
seperti Opsi B: front-channel menangani kasus yang paling sering (pengguna
menekan Keluar) secara instan, polling 60 detik menangkap sisanya.

**Pekerjaan:** kecil–sedang. Endpoint + route di sys-modern, dan penambahan tag
di halaman logout dashboard. Tidak ada perubahan di auth-sso-api.

**Catatan implementasi:** balas dengan gambar 1×1 atau HTTP 204, bukan redirect
atau HTML — `<img>` tidak bisa mengikuti alur halaman, dan respons yang tidak
bisa didekode akan tercatat sebagai error di konsol browser tanpa alasan yang
jelas.

---

## 7. Perbandingan

| | Sekarang | A — Interval 5 detik | B — Back-channel + polling | C — Back-channel saja | D — Front-channel + polling |
|---|---|---|---|---|---|
| Jeda logout | ≤ 60 detik | ≤ 5 detik | **instan** | **instan** | **instan** |
| Tambahan waktu muat halaman | tidak ada | **+100–300 ms** | tidak ada | tidak ada | tidak ada |
| Beban ke auth-sso-api | rendah | **±12× lebih tinggi** | lebih rendah dari sekarang | paling rendah | seperti sekarang |
| auth-sso-api di jalur render halaman | tidak | **ya** | tidak | tidak | tidak |
| Jaring pengaman bila gagal | — | — | ada (60 detik) | **tidak ada** | ada (60 detik) |
| Menyentuh `revokeSession()` | tidak | tidak | **ya** | ya | **tidak** |
| Menyentuh repositori lain | tidak | tidak | ya (auth-sso-api) | ya | ya (halaman logout dashboard) |
| Menjangkau pencabutan di luar tombol Keluar | ya (lewat polling) | ya | **ya** | ya | **hanya lewat polling** |
| Besar pekerjaan | — | sangat kecil | sedang | sedang | kecil–sedang |

---

## 8. Rekomendasi

**Opsi D sekarang, Opsi B kalau nanti dibutuhkan.**

Setelah koreksi di bagian 6, Opsi D menjadi jalan yang paling masuk akal untuk
dikerjakan lebih dulu. Ia memberi hasil yang sama dengan Opsi B pada kasus yang
sebenarnya kita keluhkan — pengguna menekan Keluar di dashboard lalu berharap
sys ikut tertutup — **tanpa menyentuh `revokeSession()` sama sekali**. Itu
menghilangkan satu-satunya bagian yang berisiko mengganggu HR dan CRM.

Batas Opsi D nyata dan harus diterima secara sadar: ia hanya menjangkau logout
yang dilakukan dari halaman dashboard. Pencabutan lewat jalur lain tetap
mengandalkan polling 60 detik — yaitu perilaku hari ini, tidak lebih buruk.

**Opsi B tetap jawaban yang benar** bila suatu saat pencabutan di luar tombol
Keluar juga harus instan (misalnya admin mencabut sesi seseorang dan ingin
efeknya segera), atau bila ada aplikasi anggota yang pindah ke domain berbeda
sehingga front-channel berhenti bekerja. Opsi D tidak menutup jalan ke sana —
keduanya memakai jaring pengaman polling yang sama, dan endpoint front-channel
dapat hidup berdampingan dengan endpoint back-channel.

Alasan lama di bawah ini tetap berlaku untuk perbandingan A versus B/D:

Alasan utamanya bukan kecepatan, melainkan arah biayanya. Opsi A membeli
kecepatan logout dengan memperlambat **setiap halaman untuk setiap pengguna,
sepanjang hari** — padahal logout adalah peristiwa yang jarang. Opsi B membayar
sekali di muka berupa pekerjaan, lalu justru membuat kondisi normal **lebih
murah** daripada hari ini, karena `AuthFilter` tidak lagi memanggil jaringan.

Opsi B juga sejalan dengan arah yang sudah disebut dalam dokumen rancangan SSO
(`2026-07-14-sso-signed-jwt-ticket-design.md`), yang menyebut evolusi ke OIDC
sebagai kelanjutan alami. Back-channel logout adalah salah satu bagian dari OIDC
— mengerjakannya sekarang berarti satu langkah maju ke sana, bukan pekerjaan
sementara yang nanti dibuang.

**Bila tim menilai risiko menyentuh `revokeSession()` belum sepadan sekarang,**
Opsi D menjawab kebutuhannya tanpa risiko itu sama sekali. Opsi A tetap sah
sebagai langkah paling cepat bila bahkan Opsi D pun dinilai belum prioritas.

---

## 9. Rincian Pekerjaan

### Bila Opsi D dipilih

| Repositori | Perubahan |
|---|---|
| sys-modern | Endpoint `GET sso/logout-callback` di `SsoAuth` yang **mencocokkan `sid` dari query dengan `sso_sid` pada sesi pemanggil**, lalu mengakhiri sesi itu dan membalas gambar 1×1 atau HTTP 204; route + pengecualian dari filter `auth`/`acl`; test |
| auth-sso (dashboard) | Halaman logout memuat `<img src="…/sso/logout-callback?sid=…">` untuk setiap aplikasi anggota |
| auth-sso-api | **tidak ada perubahan** |

Pencocokan `sid` itu bukan formalitas: tanpanya endpoint ini menjadi tombol
"logout paksa" yang bisa ditembakkan siapa pun dari halaman mana pun hanya
dengan menaruh sebuah `<img>`. Yang boleh diakhiri hanyalah sesi milik browser
yang memanggilnya, dan hanya bila `sid`-nya memang cocok.

Balas dengan gambar 1×1 atau HTTP 204 — bukan redirect atau HTML. `<img>` tidak
bisa mengikuti alur halaman, dan respons yang tidak bisa didekode hanya akan
mengotori konsol browser tanpa alasan yang jelas.

### Bila Opsi B dipilih

#### Di sys-modern

| Berkas | Perubahan |
|---|---|
| `app/Controllers/SsoAuth.php` | Method baru `backchannelLogout()` — memverifikasi `x-slo-secret`, membaca `sid`, mencatatnya sebagai tercabut |
| `app/Libraries/SsoRevokedSessions.php` *(baru)* | Penyimpan daftar sid tercabut. Pola sama dengan `SsoNonceStore` yang sudah ada: berkas di `writable/`, dengan TTL dan pembersihan berkala |
| `app/Filters/AuthFilter.php` | Periksa daftar lokal lebih dulu; hanya bila tidak tercatat, lanjut ke polling seperti sekarang |
| `app/Config/Routes.php` | Route `POST sso/backchannel-logout`, dikecualikan dari filter `auth` dan `acl` |
| `app/Config/Filters.php` | Menambahkan route itu ke daftar `except` |
| `tests/unit/` | Test: notifikasi tanpa secret ditolak; secret benar mengakhiri sesi pada request berikutnya; polling tetap jalan sebagai cadangan |

Endpoint ini **tidak** boleh menerima `sid` tanpa secret. Sebuah `sid` bukan
kredensial — tanpa penjagaan itu, siapa pun yang pernah melihat satu `sid` bisa
memaksa orang lain logout.

#### Di auth-sso-api

| Berkas | Perubahan |
|---|---|
| `src/modules/auth/auth.service.ts` | Di dalam `revokeSession()`, setelah baris `sso_sessions` diperbarui: kirim notifikasi ke setiap aplikasi terdaftar. Dibungkus try/catch, batas waktu pendek, hasilnya tidak memengaruhi nilai kembalian |
| `.env` | Daftar alamat back-channel aplikasi anggota |

Titik ini dipilih karena `revokeSession()` adalah **satu-satunya tempat** sebuah
sesi SSO berakhir. Memasang fan-out di sana berarti setiap jalur pencabutan
ikut terjangkau, bukan hanya tombol Keluar di dashboard.

---

## 10. Yang Perlu Diputuskan Tim

1. **Apakah jeda 60 detik benar-benar mengganggu?** Kalau logout jarang dipakai
   dan tidak ada tuntutan kepatuhan yang mengharuskan pencabutan segera, tidak
   melakukan apa-apa juga jawaban yang sah.
2. **Siapa pemilik auth-sso-api,** dan apakah mereka setuju `revokeSession()`
   disentuh? Ini prasyarat Opsi B.
3. **Apakah HR dan CRM ikut memakai back-channel ini?** Fan-out-nya dirancang
   untuk banyak aplikasi sejak awal, jadi menambahkan mereka nanti murah — tapi
   lebih baik diketahui sekarang supaya bentuk konfigurasinya sesuai.
4. **Berapa jeda yang dianggap "cukup instan"?** Kalau jawabannya "di bawah 5
   detik sudah cukup", Opsi A menyelesaikannya hari ini juga.

---

## 11. Status

**Belum ada kode yang diubah.** Dokumen ini dibuat untuk dibahas lebih dulu.
Setelah tim memilih, implementasinya dapat dikerjakan mengikuti rincian di
bagian 9.

Perilaku Single Logout hari ini tetap berjalan sebagaimana adanya: logout di
dashboard SSO mengakhiri sesi sys-modern dalam waktu paling lama 60 detik.
