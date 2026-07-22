# BankFlow

[English](README.md) | [فارسی](README.fa.md)

BankFlow یک سرویس Laravel برای ثبت و پردازش درخواست‌های تسهیلات شخصی و تجاری است. به هر درخواست، یک Workflow نسخه‌بندی‌شده و تغییرناپذیر اختصاص داده می‌شود؛ پردازش، Stageهای پیکربندی‌شده را به‌صورت هم‌زمان اجرا می‌کند و برای هر Stage اجراشده نتیجه‌ای قابل ممیزی ثبت می‌کند.

این مخزن شامل JSON API نسخه‌بندی‌شده، پنل مدیریت Filament برای تسهیلات و پیکربندی Workflow، محیط تولید مبتنی بر PostgreSQL و مجموعه تست Pest است.

## قابلیت‌های اصلی

- ثبت و مشاهده درخواست‌های تسهیلات از طریق `/api/v1/loans`؛
- اجرای مراحل اعتبارسنجی، تقلب، ضامن، اعتبار مالی و تأیید مشروط مدیر؛
- توقف پردازش در حالت رد یا بررسی دستی و بازگرداندن امن نتیجه نهایی در درخواست‌های تکراری؛
- پیکربندی و انتشار Workflowهای نسخه‌بندی‌شده برای هر نوع تسهیلات از طریق پنل مدیریت؛
- نگهداری نتیجه مراحل و Snapshot دقیق قوانین استفاده‌شده برای هر تصمیم؛
- بررسی آمادگی سرویس از طریق `GET /health`.

قرارداد کامل HTTP در [docs/api-routes.md](docs/api-routes.md) قرار دارد.

## فناوری‌ها

- PHP 8.4 و Laravel 13
- Filament 5 و Livewire 4
- PostgreSQL 17 در محیط Compose؛ پشتیبانی از SQLite برای تست‌ها و Image مستقل
- Eloquent ORM
- Pest 4 و PHPUnit 12
- Tailwind CSS 4 و Vite 8
- Apache در Container تولید

## اجرا با Docker

Image مستقل و سازگار با قرارداد چالش از پایگاه داده SQLite داخلی استفاده می‌کند. Entrypoint هنگام راه‌اندازی Migrationها و Seederها را اجرا می‌کند.

```bash
docker build -t bankflow .
docker run --rm -p 8080:8080 bankflow
```

پس از اجرا، سرویس روی پورت `8080` در دسترس است. برای بررسی سلامت آن اجرا کنید:

```bash
curl http://127.0.0.1:8080/health
```

برای اجرا با PostgreSQL، فایل محیطی را کپی کنید، مقادیر `APP_KEY` و `DB_PASSWORD` را تنظیم کنید و سپس Compose را اجرا کنید:

```bash
cp .env.example .env
docker compose up --build
```

Compose داده‌های PostgreSQL را در Volume با نام `postgres-data` نگه می‌دارد. هر دو روش Container، Migrationها و `BankFlowSeeder` تکرارپذیر را به‌طور خودکار اجرا می‌کنند.

## توسعه محلی

### پیش‌نیازها

- PHP 8.4 به‌همراه Extensionهای مورد نیاز Laravel و پایگاه داده انتخاب‌شده
- Composer 2
- Node.js 24 و npm
- PostgreSQL، یا SQLite برای محیط محلی سبک‌تر
- Laravel Herd یا یک وب‌سرور محلی پیکربندی‌شده دیگر

برنامه را نصب و پیکربندی کنید:

```bash
cp .env.example .env
composer install
php artisan key:generate
npm install
npm run build
php artisan migrate --seed
```

پیش از اجرای Migration، مقادیر `DB_*` در `.env` را متناسب با پایگاه داده در دسترس میزبان تغییر دهید. برای استفاده از SQLite، مقدار `DB_CONNECTION=sqlite` را تنظیم کنید، سایر مقادیر `DB_*` را حذف کنید و در صورت نبود فایل `database/database.sqlite` آن را بسازید.

برای توسعه فعال Frontend، فرمان `npm run dev` را اجرا کنید. در محیط استاندارد پروژه، برنامه از قبل توسط Laravel Herd سرو می‌شود و نیازی به اجرای PHP Development Server دیگری نیست.

پنل مدیریت در مسیر `/admin` قرار دارد و به کاربر احرازهویت‌شده با ایمیل تأییدشده نیاز دارد.

## نمونه API

یک درخواست تسهیلات ایجاد و پردازش کنید:

```bash
curl -X POST http://127.0.0.1:8080/api/v1/loans \
  -H 'Content-Type: application/json' \
  -d '{
    "customerId": "C-1001",
    "amount": 400000000,
    "phone": "09121234567",
    "loanType": "PERSONAL",
    "monthlyIncome": 50000000,
    "creditScore": 720,
    "hasGuarantor": false
  }'
```

از `loanId` بازگردانده‌شده استفاده کنید:

```bash
curl -X POST http://127.0.0.1:8080/api/v1/loans/{loanId}/process
curl http://127.0.0.1:8080/api/v1/loans/{loanId}
curl http://127.0.0.1:8080/api/v1/loans/{loanId}/history
```

## تست و قالب‌بندی

محیط تست از پایگاه داده SQLite درون حافظه استفاده می‌کند و به PostgreSQL نیاز ندارد.

```bash
php artisan test --compact
vendor/bin/pint --format agent
```

برای اجرای یک تست متمرکز از `php artisan test --compact --filter=ProcessLoanTest` استفاده کنید. راهبرد تست و پوشش فعلی در [TESTING.fa.md](TESTING.fa.md) توضیح داده شده است.

## ساختار پروژه

```text
app/Actions/                 عملیات مستقل برنامه و Transactionها
app/Domain/Loan/             Enumها، قراردادها و Handlerهای Stage و Engine
app/Filament/                Resourceها، Pageها و Widgetهای مدیریتی
app/Http/                    Controllerهای API، پردازش Request و Resourceها
app/Models/                  مدل‌های ماندگاری Eloquent
config/workflow-stages.php   Registry امن نگاشت کد Stage به Handler
database/                    Migrationها، Factoryها و داده اولیه Workflow
docs/                        نیازمندی‌ها، قرارداد API و Blueprint پیاده‌سازی
routes/                      Routeهای وب و API نسخه‌بندی‌شده
tests/                       تست‌های Unit و Feature با Pest
```

## مستندات بیشتر

- [DESIGN.fa.md](DESIGN.fa.md) — معماری اجرا، مدل Workflow، وضعیت و توسعه‌پذیری
- [ENGINEERING_DECISIONS.fa.md](ENGINEERING_DECISIONS.fa.md) — گزینه‌ها، Trade-offها، محدودیت‌ها و کارهای آینده
- [TESTING.fa.md](TESTING.fa.md) — دامنه تست و فرمان‌های اجرا
- [docs/bank-flow.md](docs/bank-flow.md) — نیازمندی‌های اصلی چالش
- [docs/project-blueprint.md](docs/project-blueprint.md) — Blueprint تفصیلی پیاده‌سازی
