# CLOSED ROUTES (Dilindungi `auth`)

Semua route di bawah memakai middleware `auth` PLUS lapisan tambahan (`role`/`permission`). Nama route diambil dari `php artisan route:list`.

## A. Auth + Role `requester,admin,superadmin,manager,dispatcher` → prefix `spd`
| Method | URI | Name |
|--------|-----|------|
| GET | spd/surat/{surat}/print | spd.surat.print |
| GET | spd/laporan/{laporan}/print | spd.laporan.print |
| GET | spd/laporan/{laporan}/export | spd.laporan.export |

## B. Auth (semua role login) → profil
| Method | URI | Name |
|--------|-----|------|
| GET/POST | logout | logout |
| GET | profile | profile |
| PUT | profile/change-password | profile.change-password |

## C. Auth + Role `superadmin,requester,general_affairs,dispatcher,manager` → prefix `pelaporan`
| Method | URI | Name |
|--------|-----|------|
| GET | pelaporan | pelaporan.index |
| POST | pelaporan/analyze-struk | pelaporan.analyze-struk |
| POST | pelaporan/vehicle-budget | pelaporan.vehicle-budget |
| POST | pelaporan/spd | pelaporan.spd.store |
| POST | pelaporan/operational | pelaporan.operational.store |
| GET | pelaporan/spd/{surat}/daily (+create, +{daily}/edit) | pelaporan.spd.daily.* |
| POST | pelaporan/spd/{surat}/daily | pelaporan.spd.daily.store |
| PUT | pelaporan/spd/{surat}/daily/{daily} | pelaporan.spd.daily.update |
| DELETE | pelaporan/spd/{surat}/daily/{daily} | pelaporan.spd.daily.destroy |
| POST | pelaporan/spd/{surat}/daily/item | pelaporan.spd.daily.upload-item |
| POST | pelaporan/spd/{surat}/daily/items | pelaporan.spd.daily.upload-items |
| PATCH | pelaporan/spd/{surat}/daily/item/{item} | pelaporan.spd.daily.update-item |
| DELETE | pelaporan/spd/{surat}/daily/item/{item} | pelaporan.spd.daily.delete-item |
| GET | pelaporan/spd/{surat}/validasi | pelaporan.spd.validasi |
| POST | pelaporan/spd/{surat}/finalize | pelaporan.spd.finalize |

## D. Auth + Permission `bbm.request,bbm.view,bbm.report` → requester
| Method | URI | Name |
|--------|-----|------|
| GET | requester/view-request-bbm | requester-index |
| POST | requester/create-request-bbm | requester-create |
| PUT | requester/cancel/{id} | requester-cancel |
| PUT | requester/cancel-request/{id} | requester.cancel-request |
| GET | requester/bbm-history | requester-history |
| GET | requester/request/{id} | requester-show |
| POST | requester/request/{id}/upload-proof | requester-upload-proof |
| POST | requester/request/{id}/submit-report | requester.submit-report |

> **Pengecualian:** `GET voucher/{id}/thermer/response` (`voucher.thermer.response`)
> sengaja **publik** karena diunduh langsung oleh aplikasi Android Thermer /
> Bluetooth Print tanpa cookie session. Pengganti `auth` adalah **signed URL**
> berumur 1 jam (`URL::temporarySignedRoute`) yang diverifikasi di controller;
> tanpa signature valid endpoint membalas JSON 403, bukan HTML.

### requester.surat (permission spd.view)
| Method | URI | Name |
|--------|-----|------|
| GET | surat_requester/main-surat | requester.surat.index |
| GET | surat_requester/create | requester.surat.create |
| POST | surat_requester/store | requester.surat.store |
| GET | surat_requester/{surat} | requester.surat.show |

### requester.perpanjangan (permission spd.view)
| Method | URI | Name |
|--------|-----|------|
| GET | perpanjangan/main-perpanjangan | requester.perpanjangan.index |
| GET | perpanjangan/create | requester.perpanjangan.create |
| POST | perpanjangan/store | requester.perpanjangan.store |

### requester.lpj (permission lpj.create,lpj.view)
| Method | URI | Name |
|--------|-----|------|
| GET | lpj/main-lpj | requester.lpj.index |
| GET | lpj/create/{surat} | requester.lpj.create |
| POST | lpj/store | requester.lpj.store |
| GET | lpj/show/{laporan} | requester.lpj.show |
| GET | lpj/{laporan}/edit | requester.lpj.edit |
| PUT | lpj/{laporan} | requester.lpj.update |

### requester.ajax (permission spd.view,bbm.view)
| Method | URI | Name |
|--------|-----|------|
| GET | ajax/surat/{surat} | requester.ajax.surat.detail |
| GET | ajax/surat-list | requester.ajax.surat.list |
| GET | ajax/dashboard-stats | requester.ajax.dashboard.stats |

### requester.vehicle-check
| Method | URI | Name |
|--------|-----|------|
| GET | vehicle-check/acknowledgement | requester.checking-acknowledgement.index |
| GET | vehicle-check/acknowledgement/{checkingDetailCode} | requester.checking-acknowledgement.show |
| PUT | vehicle-check/acknowledgement/{checkingDetailCode}/ack | requester.checking-acknowledgement.acknowledge |
| GET | vehicle-check/replacement-schedule | requester.replacement-schedule.index |
| GET | vehicle-check/service-transaction | requester.service-transaction.index |
| GET | vehicle-check/service-transaction/create/{vprCode} | requester.service-transaction.create |
| POST | vehicle-check/service-transaction/{vprCode} | requester.service-transaction.store |

## E. Auth + Role `admin,superadmin`
| Method | URI | Name |
|--------|-----|------|
| GET | admin/dashboard | admin-dashboard |
| GET | admin/approval | admin-approval |
| POST | admin/approval/{id}/process | admin-process |
| POST | admin/approval/bulk-process | admin-bulk-process |
| GET | transaction-photo/{reportId}/{type} | transaction.getPhoto |
| GET | vouchers/list | admin.vouchers.list |
| POST | vouchers/export | admin.vouchers.export |
| GET | admin/service-scheduling | admin.service-scheduling.index |
| GET | admin/replacement-approval (+, {vprCode}) | admin.replacement-approval.* |
| POST | admin/replacement-approval/{vprCode}/approve | admin.replacement-approval.approve |
| POST | admin/replacement-approval/{vprCode}/reject | admin.replacement-approval.reject |
| POST | admin/replacement-approval/{vprCode}/schedule | admin.replacement-approval.schedule |
| GET | admin/transaction-monitoring | admin.transaction-monitoring.index |
| GET | master-gasoline | master-gasoline.index |
| POST | master-gasoline/sync | master-gasoline.sync |

## F. Auth + Permission `vehicle.sparepart` → admin/sparepart-request
| Method | URI | Name |
|--------|-----|------|
| GET | admin/sparepart-request (+, {vprCode}) | admin.sparepart-request.* |
| POST | admin/sparepart-request/{vprCode}/approve | admin.sparepart-request.approve |
| POST | admin/sparepart-request/{vprCode}/reject | admin.sparepart-request.reject |
| POST | admin/sparepart-request/{vprCode}/schedule | admin.sparepart-request.schedule |
| POST | admin/sparepart-request/{vprCode}/complete | admin.sparepart-request.complete |

## G. Auth + Role `superadmin,admin`
| Method | URI | Name |
|--------|-----|------|
| GET | print-report | superadmin.print-report |
| GET | print-report/form | superadmin.print-report.form |

## H. Auth + Role `superadmin,admin,admin_bbm,dispatcher,manager,non_ga_staff,general_affairs,view_only`
| Method | URI | Name |
|--------|-----|------|
| GET | ai-assistant | ai-assistant.index |
| POST | ai-assistant/send | ai-assistant.send |
| DELETE | ai-assistant/sessions/{session} | ai-assistant.sessions.destroy |

## I. Auth + Role `superadmin` (Dashboard, User, Vehicle, Permohonan, Laporan, Trip, RBAC, Unit, Site, DataMart, Analytics, SPD Surat, Perpanjangan, LPJ, Activity Log, Vehicle Checking)
| Method | URI | Name |
|--------|-----|------|
| GET | sa/dashboard | superadmin-dashboard |
| GET | dashboard/stats | dashboard.stats |
| GET | dashboard/driver-stats | dashboard.driver-stats |
| GET | dashboard-bisnis | superadmin.dashboard.bisnis |
| GET | users (+create, /{id}/edit) | users.* |
| POST | users/store | users.store |
| POST | users/{id}/update | users.update |
| POST | users/{id}/delete | users.delete |
| POST | users/{id}/reset-password | users.reset-password |
| POST | users/{id}/restore | users.restore |
| GET | vehicles (+create, /{id}/edit, /{id}/detail, /{id}/detail/edit) | vehicles.* / superadmin.vehicle.* |
| POST | vehicles/store, /{id}/update, /{id}/delete, /{id}/restore | vehicles.* |
| POST | vehicles/{id}/approve, reject, activate, deactivate, bulk-* | superadmin.vehicle.* |
| POST | vehicle-activation ... | superadmin.vehicle-activation |
| GET | kelola-permohonan | superadmin.permohonan |
| POST | permohonan/{id}/update, cancel, delete | superadmin.permohonan.* |
| POST | kelola-permohonan/bulk-process | superadmin.permohonan.bulk-process |
| POST | laporan/{id}/update, delete | superadmin.laporan.* |
| GET | trip-analysis | superadmin.trip-analysis.index |
| POST | trip-analysis/recalculate-all | superadmin.trip-validations.recalculate-all |
| POST | trip-validations/{id}/validate | superadmin.trip-validations.validate |
| POST | trip-validations/bulk-validate | superadmin.trip-validations.bulk-validate |
| GET | api/dashboard/* (6x), api/data-mart/{unitId}(+/refresh) | superadmin.* / api.datamart.* |
| GET | analytics/* (3x) | analytics.* |
| GET | surat (+create-shortcut, {surat}, {surat}/print, report/spending) | surat.superadmin.* |
| POST | surat/store-shortcut, /{surat}/approve, reject, save-mendadak-budget | surat.superadmin.* |
| GET | perpanjangan (+validate/{perpanjangan}) | perpanjangan.superadmin.* |
| POST | perpanjangan/{perpanjangan}/approve, reject, acknowledge | perpanjangan.superadmin.* |
| GET | lpj (+{laporan}, {laporan}/print) | lpj.superadmin.* |
| POST | lpj/{laporan}/approve, reject | lpj.superadmin.* |
| PUT | lpj/{laporan}/keuangan | lpj.superadmin.keuangan |
| GET | ajax/dashboard-stats | ajax.superadmin.dashboard.stats |
| GET | sa/rbac (+create, /{id}/edit) | superadmin.rbac.* |
| POST | sa/rbac, /{id}/update, /{id}/delete | superadmin.rbac.* |
| GET | sa/units (+create, /{unit}/edit), sa/sites (+create, /{site}/edit) | superadmin.units.* / superadmin.sites.* |
| POST | sa/units, sa/units/{unit}/delete, sa/sites, sa/sites/{site}/delete | superadmin.units.* / superadmin.sites.* |
| PUT | sa/units/{unit}, sa/sites/{site} | superadmin.units.update / superadmin.sites.update |
| GET | activity-log | activity-log |
| GET | vehicle-checking/vehicle-health (+, /{consumerialCode}) | superadmin.vehicle-health.* |
| GET | vehicle-checking/financial-report (+, /export) | superadmin.financial-report.* |
| GET | vehicle-checking/transaction-report | superadmin.transaction-report.index |
| GET | vehicle-checking/barcode/download/{vehicleId} | superadmin.barcode.download |
| GET | vehicle-checking/barcode/download-all-zip | superadmin.barcode.download-zip |

## J. Auth + Role `manager,superadmin`
| Method | URI | Name |
|--------|-----|------|
| GET | dashboard-bisnis | superadmin.dashboard.bisnis |

## K. Auth + Permission `vehicle.checking` → dispatcher
| Method | URI | Name |
|--------|-----|------|
| GET | dispatcher/scan-vehicle (+/result) | dispatcher.scan-vehicle.* |
| GET | dispatcher/dispatcher-scanner (+/result) | dispatcher.dispatcher-scanner.* |
| GET | dispatcher/barcode/download/{vehicleId} | dispatcher.barcode.download |

## L. Auth + Role `dispatcher,superadmin` → dispatcher
| Method | URI | Name |
|--------|-----|------|
| GET | dispatcher/assign | dispatcher.assign.index |
| PUT | dispatcher/assign | dispatcher.assign.update |
| GET | dispatcher/dashboard | dispatcher.dispatcher-dashboard |
| POST | dispatcher/generate-period | dispatcher.generate-period |
| GET | dispatcher/daily-check (+create, /{checkingDetailCode}) | dispatcher.daily-check.* |
| POST | dispatcher/daily-check | dispatcher.daily-check.store |
| GET | dispatcher/weekly-check (+create, /{checkingDetailCode}) | dispatcher.weekly-check.* |
| POST | dispatcher/weekly-check | dispatcher.weekly-check.store |
| GET | dispatcher/monthly-check (+create, /{checkingDetailCode}) | dispatcher.monthly-check.* |
| POST | dispatcher/monthly-check | dispatcher.monthly-check.store |
| GET | dispatcher/body-check (+create, /{checkingDetailCode}) | dispatcher.body-check.* |
| POST | dispatcher/body-check | dispatcher.body-check.store |
| GET | dispatcher/history | dispatcher.history |
| POST | dispatcher/export/vehicle-checklist | dispatcher.export.vehicle-checklist |
| POST | dispatcher/export/routine-check | dispatcher.export.routine-check |
| POST | dispatcher/export/body-check | dispatcher.export.body-check |
| GET | dispatcher/replacement (+create/auto-create/show) | dispatcher.replacement.* |
| POST | dispatcher/replacement/store/{checkingDetailCode}, auto-create/{...} | dispatcher.replacement.* |

## M. Auth + Role `admin,superadmin` → lunch
| Method | URI | Name |
|--------|-----|------|
| GET | lunch | lunch.dashboard |
| GET | lunch/forecasts | lunch.forecasts.index |
| POST | lunch/forecasts/generate, override | lunch.forecasts.* |
| GET | lunch/distributions | lunch.distributions.index |
| POST | lunch/distributions/generate, lock | lunch.distributions.* |
| GET | lunch/export, lunch/qr-codes, /qr-codes/{site}.svg | lunch.export-form / lunch.qr-* |
| POST | lunch/export | lunch.export |
| GET | lunch/transactions (+/feed) | lunch.transactions.* |
| GET | lunch/analytics | lunch.analytics.index |
| GET | lunch/guests | lunch.guests.calendar |
| POST | lunch/guests | lunch.guests.store |
| PUT | lunch/guests/{cateringGuest} | lunch.guests.update |
| DELETE | lunch/guests/{cateringGuest} | lunch.guests.destroy |
| POST | lunch/guests/{cateringGuest}/h2-confirm, h2-cancel | lunch.guests.* |
| POST | lunch/self-booking | lunch.self-booking.store |
| GET | lunch/self-booking | lunch.self-booking.index |

## N. Auth + Permission `lunch.manage` → lunch/employee-defaults
| Method | URI | Name |
|--------|-----|------|
| GET/POST | lunch/employee-defaults (+export, +import) | lunch.employee-defaults.* |
| PUT/DELETE | lunch/employee-defaults/{employeeDefault} | lunch.employee-defaults.update / destroy |
| POST | lunch/employee-defaults/{employeeDefault}/restore | lunch.employee-defaults.restore |

## O. Auth + Role `vendor,superadmin`
| Method | URI | Name |
|--------|-----|------|
| GET | vendor/lunch/today | vendor.lunch.today |

## Route Terdaftar Tanpa Group (dari framework/console)
- GET/PUT `storage/{path}` sudah masuk tabel PUBLIC (signed URL).
- `GET /up` (health check) — dari `withRouting(health:)`, publik.