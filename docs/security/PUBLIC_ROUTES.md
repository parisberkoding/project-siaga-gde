# PUBLIC ROUTES (Tidak Dilindungi `auth`)

Daftar route yang dapat diakses tanpa login. Semua membawa middleware `web` saja (Session, CSRF, dll dari grup `web`).

| # | Method | URI | Name | Action | Middleware |
|---|--------|-----|------|--------|------------|
| 1 | GET | `/` | welcome | `routes/web.php:51` (closure view) | web |
| 2 | GET | `/tentang` | — | `routes/web.php:43` (closure view) | web |
| 3 | GET | `/panduan` | — | `routes/web.php:47` (closure view) | web |
| 4 | GET | `/login` | login | `AuthentiactionController@index` | web |
| 5 | POST | `/login-process` | login-process | `AuthentiactionController@authenticate` | web |
| 6 | GET | `/files/storage/{path}` | storage.serve | `StorageFileController@serve` | web |
| 7 | GET | `/storage/{path}` | storage.local | Framework `ServeFile` | web (signed URL) |
| 8 | PUT | `/storage/{path}` | storage.local.upload | Framework `ReceiveFile` | web (signed URL) |
| 9 | GET | `/lunch-attendance` | lunch.attendance.scan | `PublicAttendanceController@landing` | web |
| 10 | GET | `/lunch-attendance/site/{siteCode}` | lunch.attendance.form | `PublicAttendanceController@form` | web |
| 11 | GET | `/lunch-attendance/lookup` | lunch.attendance.lookup | `PublicAttendanceController@lookup` | web + throttle:60,1 |
| 12 | POST | `/lunch-attendance/submit` | lunch.attendance.submit | `PublicAttendanceController@submit` | web + throttle:15,1 |
| 13 | POST | `/lunch-attendance/tomorrow/{transaction}` | lunch.attendance.tomorrow | `PublicAttendanceController@confirmTomorrow` | web + throttle:15,1 |
| 14 | POST | `/lunch-attendance/guest-submit` | lunch.attendance.guest-submit | `PublicAttendanceController@guestSubmit` | web + throttle:15,1 |
| 15 | POST | `/lunch-attendance/{transaction}/choose-site` | lunch.attendance.choose-site | `PublicAttendanceController@chooseSite` | web + throttle:30,1 |
| 16 | POST | `/lunch-attendance/{transaction}/choose-dine-type` | lunch.attendance.choose-dine-type | `PublicAttendanceController@chooseDineType` | web + throttle:30,1 |
| 17 | GET | `/my/lunch-plan` | my.lunch-plan | `Lunch\SelfBookingController@index` | web |
| 18 | POST | `/my/lunch-plan/store` | my.lunch-plan.store | `Lunch\SelfBookingController@store` | web |

## Catatan Eksposur

- **Ziggy tidak digunakan** di project ini (tidak ada `route()` helper JS / `@routes` direktur). Semua URL JS dibuat via `@json(route(...))` secara manual, sehingga tidak ada eksposur seluruh route ke frontend.
- Route framework `/storage/{path}` **GET & PUT** muncul karena disk `local` mengaktifkan `'serve' => true` (`config/filesystems.php`). Keduanya dilindungi URL signature (harus punya `signature` query param), tapi PUT `/storage/{path}` adalah endpoint **upload** publik berbasis signed URL — perlu ditinjau apakah disengaja.
- Route nomor 6 (`/files/storage/{path}`) dan 17-18 (`/my/lunch-plan`) adalah **titik risiko utama** — lihat LAPORAN.