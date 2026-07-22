# تست BankFlow

[English](TESTING.md) | [فارسی](TESTING.fa.md)

BankFlow از Pest 4 استفاده می‌کند. Feature Testها با Application Test Case مربوط به Laravel و `LazilyRefreshDatabase` اجرا می‌شوند. فایل `phpunit.xml` پایگاه داده SQLite درون حافظه، Cache و Session و Mail آرایه‌ای و Queue Driver هم‌زمان را انتخاب می‌کند.

## اجرای مجموعه تست

```bash
php artisan test --compact
```

برای اجرای تست متمرکز، برای نمونه از فرمان‌های زیر استفاده کنید:

```bash
php artisan test --compact tests/Feature/Loan/ProcessLoanTest.php
php artisan test --compact --filter="approves a valid personal loan"
```

## پوشش Unit Test

Unit Testها هر Stage کسب‌وکار را به‌طور مستقل بررسی می‌کنند:

- اعتبارسنجی موفق و دلیل اولین خطا برای هر قانون؛
- Prefixهای تقلب و بررسی دستی؛
- نتایج بررسی ضامن؛
- مرزهای اعتبار مالی و قوانین Overrideشده؛
- قابل اجرا بودن تأیید مدیر، ضریب درآمد و نتایج Pass/Fail.

این تست‌ها رفتار خالص Stage را بدون هماهنگ‌سازی HTTP یا پایگاه داده بررسی می‌کنند.

## پوشش Feature و Integration

Feature Testها ایجاد Loan، اعتبارسنجی معنایی تأخیری، Request ناقص، نوع تسهیلات خارج از دسترس، مسیرهای پردازش شخصی و تجاری، بررسی دستی تقلب یا اعتبار، تأیید مشروط مدیر، Idempotency وضعیت نهایی، دریافت اطلاعات، تاریخچه مرتب، خطاهای مستند Not Found و Health Check را پوشش می‌دهند.

Integration Testهای مدیریتی دسترسی احرازهویت‌شده به پنل، نمایش فقط‌خواندنی Loan، ویرایش Workflow فقط در وضعیت Draft، کپی نسخه Workflow، انتشار اعتبارسنجی‌شده، Archive اتمیک نسخه قبلی و تأیید محافظت‌شده مدیر را بررسی می‌کنند. این تست‌ها مرزهای HTTP/Filament، Action، Eloquent، Migration و Resource را در بر می‌گیرند.

## موارد پوشش‌داده‌نشده

مجموعه فعلی، Requestهای هم‌زمان واقعی یا رفتارهای مخصوص PostgreSQL مانند رقابت قفل ردیف و Partial Published-workflow Index را آزمایش نمی‌کند. همچنین Docker Smoke Test، Browser Test فرانت‌اند، تست Performance/Load، تست امنیت، تزریق Exception پیش‌بینی‌نشده برای بررسی Rollback و Integration واقعی تقلب یا اعتبار پوشش داده نشده‌اند؛ چالش برای این موارد از قوانین قطعی و Mockشده استفاده می‌کند.
