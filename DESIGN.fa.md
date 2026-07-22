# طراحی BankFlow

[English](DESIGN.md) | [فارسی](DESIGN.fa.md)

## معماری

BankFlow از معماری لایه‌ای Laravel با تمرکز بر دامنه تسهیلات استفاده می‌کند:

```text
HTTP Request
  -> Form Request / Filament Page
  -> Action تک‌منظوره
  -> WorkflowEngine یا Eloquent Query
  -> PostgreSQL
```

لایه HTTP مسئول ملاحظات Transport و JSON Resourceها است. Actionها Use Caseها و مرز Transactionها را نمایش می‌دهند. مسیر `app/Domain/Loan` شامل مقادیر دامنه و رفتار Workflow با کمترین وابستگی به Framework است. مدل‌های Eloquent مسئول Relationshipها و ماندگاری هستند، اما پردازش را هماهنگ نمی‌کنند.

API عمومی زیر مسیر `/api/v1` نسخه‌بندی شده است. Filament رابط مدیریتی احرازهویت‌شده را برای مشاهده تسهیلات، انواع تسهیلات، تعریف Stageها و نسخه‌های Workflow فراهم می‌کند.

## مدل و اجرای Workflow

هر `LoanType` چند نسخه `WorkflowConfiguration` دارد. هر پیکربندی شامل ردیف‌های مرتب `WorkflowConfigurationStep` است و هر Step به یک `StageDefinition` و یک شیء JSON حاوی قوانین اشاره می‌کند. فقط Workflow با وضعیت `PUBLISHED` به درخواست جدید اختصاص می‌یابد. شناسه همان پیکربندی در Loan نگهداری می‌شود؛ بنابراین تغییرات مدیریتی بعدی نمی‌توانند تصمیم در حال اجرا یا تاریخی را تغییر دهند.

فایل `config/workflow-stages.php` Registry امن پیاده‌سازی است. این فایل کد پایدار هر Stage را به یک کلاس PHP پیاده‌ساز `StageInterface` نگاشت می‌کند؛ مقادیر پایگاه داده هرگز کلاس دلخواه یا عبارت اجرایی را انتخاب نمی‌کنند. Handlerهای Stage بدون State هستند و یک `ExecutionResult` تغییرناپذیر شامل `PASS`، `FAIL` یا `MANUAL_REVIEW` و کد دلیل برمی‌گردانند.

پردازش به‌صورت هم‌زمان انجام می‌شود:

1. `ProcessLoanAction` یک Transaction پایگاه داده باز و ردیف Loan را قفل می‌کند.
2. Loan دارای وضعیت نهایی بدون اجرای دوباره بازگردانده می‌شود.
3. `WorkflowEngine` مراحل فعال را بر اساس `position` می‌خواند.
4. مراحلی که قبلاً در تاریخچه ثبت شده‌اند نادیده گرفته می‌شوند.
5. Handler مشروط می‌تواند اعلام کند که قابل اجرا نیست؛ برای Stage ردشده تاریخچه‌ای ایجاد نمی‌شود.
6. Handler با قوانین اعتبارسنجی‌شده Step اجرا می‌شود.
7. یک ردیف تاریخچه شامل Stage، نتیجه، دلیل، زمان اجرا و Snapshot قوانین ذخیره می‌شود.
8. نتیجه `FAIL` وضعیت `REJECTED` و نتیجه `MANUAL_REVIEW` وضعیت `MANUAL_REVIEW` را ایجاد می‌کند؛ در غیر این صورت Step مرتب بعدی انتخاب می‌شود.
9. عبور موفق از تمام مراحل قابل اجرا، وضعیت `APPROVED` را ایجاد می‌کند.

بنابراین Stage فعلی در یک Switch مربوط به Transitionها Hard-code نشده است؛ بلکه اولین Step فعال، قابل اجرا و اجرا‌نشده در نسخه مرتب Workflow مربوط به Loan است.

## قوانین و چرخه عمر پیکربندی

آستانه‌ها و Prefixهای کسب‌وکار در ستون JSON با نام `rules` برای هر Step قرار دارند. Workflowهای Seedشده، مقادیر پیش‌فرض چالش را تأمین می‌کنند. اعتبارسنجی اختصاصی هر Stage هنگام ایجاد یا ویرایش Draft توسط مدیر و بار دیگر پیش از انتشار اجرا می‌شود. Registry امن یک فایل پیکربندی PHP باقی می‌ماند، زیرا Handlerهای اجرایی بخشی از کد برنامه هستند؛ در مقابل، مقادیر کسب‌وکار و ترتیب مراحل به‌صورت داده‌های نسخه‌بندی‌شده در پایگاه داده قرار دارند.

نسخه‌های Workflow چرخه `DRAFT -> PUBLISHED -> ARCHIVED` را طی می‌کنند. Draft قابل ویرایش است. انتشار، کل Workflow را اعتبارسنجی و نسخه منتشرشده قبلی همان نوع تسهیلات را به‌صورت اتمیک Archive می‌کند. نسخه‌های Published و Archived تغییرناپذیر هستند.

## ماندگاری، وضعیت و تاریخچه ممیزی

Eloquent انواع تسهیلات، تعریف Stageها، نسخه‌ها و مراحل Workflow، درخواست‌های تسهیلات و تاریخچه‌ها را ذخیره می‌کند. Backed Enumها سه مفهوم را از هم جدا می‌کنند:

- وضعیت Loan: `SUBMITTED`، `IN_PROGRESS`، `MANUAL_REVIEW`، `APPROVED` و `REJECTED`؛
- Stage قابل اجرا: `VALIDATION`، `FRAUD_CHECK`، `GUARANTOR_CHECK`، `CREDIT_CHECK` و `MANAGER_APPROVAL`؛
- نتیجه Stage: `PASS`، `FAIL` و `MANUAL_REVIEW`.

فیلد `loans.current_workflow_configuration_step_id` پیشرفت فعلی را نشان می‌دهد و `loan_histories` تاریخچه پایدار کسب‌وکار است. Snapshot قوانین در تاریخچه، ورودی دقیق هر تصمیم را حتی در صورت استفاده Workflowهای آینده از قوانین متفاوت حفظ می‌کند. Exceptionهای پیش‌بینی‌نشده با Logging استاندارد Laravel ثبت می‌شوند و Transaction پردازش را Rollback می‌کنند.

درخواست‌های دارای وضعیت بررسی دستی می‌توانند بعداً در Filament توسط مدیر مجاز تأیید شوند. این عملیات Loan را قفل و مدیر تأییدکننده، زمان و یادداشت را ثبت می‌کند.

## جلوگیری از پردازش تکراری و هم‌زمان

Idempotency در چند سطح اعمال می‌شود:

- وضعیت نهایی باعث بازگشت فوری و بدون عملیات می‌شود؛
- شناسه Stepهای اجراشده از تاریخچه استخراج می‌شود و دوباره اجرا نمی‌شوند؛
- Unique Constraint روی `(loan_id, workflow_configuration_step_id)` از ایجاد تاریخچه تکراری جلوگیری می‌کند؛
- `SELECT ... FOR UPDATE` پردازش‌های هم‌زمان یک Loan را سریالی می‌کند؛
- اجرای کامل Workflow و همه تاریخچه‌ها در یک Transaction با حداکثر سه بار تلاش مجدد قرار دارند.

Constraintهای پایگاه داده نیز سازگاری نوع تسهیلات، Workflow و Step فعلی Loan را حفظ می‌کنند. PostgreSQL با یک Partial Unique Index اجازه می‌دهد برای هر نوع تسهیلات فقط یک Workflow منتشرشده وجود داشته باشد.

## افزودن `AML_CHECK`

افزودن یک Stage به تغییرات زیر نیاز دارد:

1. افزودن `AmlCheck` به `LoanStage`؛
2. پیاده‌سازی `StageInterface` و در صورت قابل ردشدن بودن، `ConditionalStageInterface`؛
3. ثبت `AML_CHECK` در `config/workflow-stages.php`؛
4. تعریف و اعتبارسنجی Schema قوانین آن در `ValidateWorkflowStepDataAction`؛
5. ایجاد یا Seed کردن `StageDefinition` و افزودن آن به یک نسخه Draft جدید؛
6. افزودن Unit Test برای Stage و Feature Test برای ترتیب، نتایج، تاریخچه و Idempotency.

Engine و کلاس‌های Stage موجود تغییر نمی‌کنند. انتشار نسخه جدید فقط بر Loanهایی اثر می‌گذارد که پس از آن ساخته شوند.

## Trade-offهای اصلی

اجرای هم‌زمان در محدوده یک Transaction، استدلال درباره صحت و رفتار API را ساده می‌کند؛ اما بررسی‌های خارجی طولانی، Request و قفل پایگاه داده را برای مدت زیادی نگه می‌دارند. ترتیب Workflow مدیریت‌شده در پایگاه داده، مدیریت امن و بازتولید تصمیم‌های تاریخی را فراهم می‌کند؛ در مقابل نسبت به پیکربندی ثابت به Schema و اعتبارسنجی انتشار بیشتری نیاز دارد. دسترسی مستقیم Eloquent برنامه را کوچک و منطبق با Laravel نگه می‌دارد، اما افزودن یک پیاده‌سازی ماندگاری دوم را پرهزینه‌تر می‌کند.
