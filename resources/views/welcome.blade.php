<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Create and process a BankFlow loan application.">

        <title>BankFlow — Loan decisions, clearly</title>

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#f4f3ee] font-sans text-slate-950 antialiased selection:bg-teal-200 selection:text-teal-950">
        <div class="pointer-events-none fixed inset-0 overflow-hidden" aria-hidden="true">
            <div class="absolute -top-32 right-[8%] size-96 rounded-full bg-[#cdeee7]/70 blur-3xl"></div>
            <div class="absolute -bottom-48 -left-20 size-[32rem] rounded-full bg-[#f5d9a8]/45 blur-3xl"></div>
        </div>

        <div class="relative mx-auto flex min-h-screen w-full max-w-[1440px] flex-col px-5 py-6 sm:px-8 lg:px-12 lg:py-8">
            <header class="flex items-center justify-between gap-4">
                <a href="{{ url('/') }}" class="group inline-flex items-center gap-3" aria-label="BankFlow home">
                    <span class="grid size-10 place-items-center rounded-2xl bg-slate-950 text-white shadow-lg shadow-slate-950/15 transition-transform group-hover:-rotate-3">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M4 17.5 8.2 13l3.1 2.8L20 6.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M15 6.5h5v5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                    <span>
                        <span class="block text-[15px] font-semibold tracking-tight">BankFlow</span>
                        <span class="block text-[10px] font-medium uppercase tracking-[0.22em] text-slate-500">Decision engine</span>
                    </span>
                </a>

                <div id="service-status" data-health-url="{{ route('health') }}" class="flex items-center gap-2 rounded-full border border-white/80 bg-white/65 px-3 py-2 text-xs font-medium text-slate-600 shadow-sm backdrop-blur-xl" aria-live="polite">
                    <span class="relative flex size-2">
                        <span id="service-status-pulse" class="absolute inline-flex size-full animate-ping rounded-full bg-slate-400 opacity-60"></span>
                        <span id="service-status-dot" class="relative inline-flex size-2 rounded-full bg-slate-400"></span>
                    </span>
                    <span id="service-status-label">Checking service</span>
                </div>
            </header>

            <main class="grid flex-1 items-center gap-10 py-12 lg:grid-cols-[0.8fr_1.2fr] lg:gap-16 lg:py-10">
                <section class="max-w-xl">
                    <div class="mb-6 inline-flex items-center gap-2 rounded-full border border-slate-900/10 bg-white/55 px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.16em] text-teal-800 backdrop-blur">
                        <span>Simple in</span>
                        <span class="text-slate-300">/</span>
                        <span>Clear out</span>
                    </div>

                    <h1 class="max-w-lg text-5xl font-semibold leading-[0.95] tracking-[-0.055em] text-slate-950 sm:text-6xl lg:text-7xl">
                        A loan journey you can see.
                    </h1>
                    <p class="mt-6 max-w-md text-base leading-7 text-slate-600 sm:text-lg">
                        Submit an application, run the decision workflow, and follow every stage from validation to outcome.
                    </p>

                    <div class="mt-9 flex items-center gap-3 text-xs font-medium text-slate-500">
                        <span class="grid size-7 place-items-center rounded-full border border-slate-300 bg-white/60 text-slate-800">1</span>
                        <span>Submit</span>
                        <span class="h-px w-7 bg-slate-300"></span>
                        <span class="grid size-7 place-items-center rounded-full border border-slate-300 bg-white/60 text-slate-800">2</span>
                        <span>Process</span>
                        <span class="h-px w-7 bg-slate-300"></span>
                        <span class="grid size-7 place-items-center rounded-full border border-slate-300 bg-white/60 text-slate-800">3</span>
                        <span>Review</span>
                    </div>

                    <form id="lookup-form" class="mt-10 flex max-w-md gap-2" novalidate>
                        <label for="lookup-loan-id" class="sr-only">Existing loan ID</label>
                        <input id="lookup-loan-id" name="loanId" type="text" autocomplete="off" placeholder="Already applied? Enter loan ID" class="min-w-0 flex-1 rounded-2xl border border-slate-900/10 bg-white/65 px-4 py-3 text-sm shadow-sm outline-none backdrop-blur placeholder:text-slate-400 focus:border-teal-600 focus:ring-4 focus:ring-teal-600/10">
                        <button type="submit" class="rounded-2xl bg-slate-950 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-slate-950/10 transition hover:-translate-y-0.5 hover:bg-teal-800 focus:outline-none focus:ring-4 focus:ring-teal-600/20">
                            Find
                        </button>
                    </form>
                </section>

                <section class="relative">
                    <div class="absolute -inset-3 -z-10 rotate-1 rounded-[2rem] bg-teal-900/8"></div>
                    <div class="overflow-hidden rounded-[1.75rem] border border-white/90 bg-white/80 shadow-[0_24px_80px_-30px_rgba(15,23,42,0.35)] backdrop-blur-xl">
                        <div class="flex items-center justify-between border-b border-slate-200/80 px-5 py-4 sm:px-7">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-teal-700">New application</p>
                                <h2 class="mt-1 text-lg font-semibold tracking-tight">Tell us the essentials</h2>
                            </div>
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-[11px] font-medium text-slate-500">~ 1 minute</span>
                        </div>

                        <form id="loan-form" class="p-5 sm:p-7" novalidate>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <label class="grid gap-1.5 text-sm font-medium text-slate-700">
                                    Customer ID
                                    <input name="customerId" type="text" value="C-1001" required class="rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm outline-none transition focus:border-teal-600 focus:ring-4 focus:ring-teal-600/10">
                                </label>

                                <label class="grid gap-1.5 text-sm font-medium text-slate-700">
                                    Mobile number
                                    <input name="phone" type="tel" inputmode="numeric" value="09121234567" pattern="09[0-9]{9}" required class="rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm outline-none transition focus:border-teal-600 focus:ring-4 focus:ring-teal-600/10">
                                </label>

                                <label class="grid gap-1.5 text-sm font-medium text-slate-700">
                                    Requested amount
                                    <span class="relative">
                                        <input name="amount" type="number" inputmode="numeric" min="1" step="1" value="400000000" required class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 pr-14 text-sm outline-none transition focus:border-teal-600 focus:ring-4 focus:ring-teal-600/10">
                                        <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-xs text-slate-400">IRR</span>
                                    </span>
                                </label>

                                <label class="grid gap-1.5 text-sm font-medium text-slate-700">
                                    Monthly income
                                    <span class="relative">
                                        <input name="monthlyIncome" type="number" inputmode="numeric" min="0" step="1" value="50000000" required class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 pr-14 text-sm outline-none transition focus:border-teal-600 focus:ring-4 focus:ring-teal-600/10">
                                        <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-xs text-slate-400">IRR</span>
                                    </span>
                                </label>
                            </div>

                            <fieldset class="mt-5">
                                <legend class="text-sm font-medium text-slate-700">Loan type</legend>
                                <div class="mt-2 grid grid-cols-2 gap-2">
                                    <label class="relative cursor-pointer">
                                        <input class="peer sr-only" type="radio" name="loanType" value="PERSONAL" checked>
                                        <span class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white px-3.5 py-3 text-sm transition peer-checked:border-teal-700 peer-checked:bg-teal-50 peer-checked:text-teal-900 peer-focus-visible:ring-4 peer-focus-visible:ring-teal-600/10">
                                            <span class="grid size-8 place-items-center rounded-lg bg-slate-100 text-slate-600 peer-checked:bg-white">
                                                <svg class="size-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM4.5 20c.8-3.4 3.3-5.5 7.5-5.5s6.7 2.1 7.5 5.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                                            </span>
                                            Personal
                                        </span>
                                    </label>
                                    <label class="relative cursor-pointer">
                                        <input class="peer sr-only" type="radio" name="loanType" value="BUSINESS">
                                        <span class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white px-3.5 py-3 text-sm transition peer-checked:border-teal-700 peer-checked:bg-teal-50 peer-checked:text-teal-900 peer-focus-visible:ring-4 peer-focus-visible:ring-teal-600/10">
                                            <span class="grid size-8 place-items-center rounded-lg bg-slate-100 text-slate-600">
                                                <svg class="size-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 20V8h16v12M8 8V4h8v4M2 20h20M8 12h2m4 0h2m-8 4h2m4 0h2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                            </span>
                                            Business
                                        </span>
                                    </label>
                                </div>
                            </fieldset>

                            <div class="mt-5 grid gap-5 sm:grid-cols-[1fr_auto] sm:items-end">
                                <label class="grid gap-2 text-sm font-medium text-slate-700">
                                    <span class="flex items-center justify-between gap-3">
                                        Credit score
                                        <output id="credit-score-output" for="credit-score" class="rounded-md bg-slate-950 px-2 py-0.5 font-mono text-xs text-white">720</output>
                                    </span>
                                    <input id="credit-score" name="creditScore" type="range" min="0" max="1000" value="720" class="h-2 w-full cursor-pointer appearance-none rounded-full bg-slate-200 accent-teal-700">
                                </label>

                                <label class="flex cursor-pointer items-center justify-between gap-4 rounded-xl border border-slate-200 bg-white px-3.5 py-3 text-sm font-medium text-slate-700 sm:min-w-44">
                                    Has guarantor
                                    <span class="relative inline-flex">
                                        <input name="hasGuarantor" type="checkbox" class="peer sr-only">
                                        <span class="h-6 w-11 rounded-full bg-slate-200 transition peer-checked:bg-teal-700 peer-focus-visible:ring-4 peer-focus-visible:ring-teal-600/10"></span>
                                        <span class="absolute left-1 top-1 size-4 rounded-full bg-white shadow transition peer-checked:translate-x-5"></span>
                                    </span>
                                </label>
                            </div>

                            <div id="form-message" class="mt-5 hidden rounded-xl px-3.5 py-3 text-sm" role="alert"></div>

                            <button id="submit-loan" type="submit" class="mt-5 flex w-full items-center justify-center gap-2 rounded-xl bg-teal-800 px-4 py-3.5 text-sm font-semibold text-white shadow-lg shadow-teal-900/15 transition hover:-translate-y-0.5 hover:bg-teal-900 focus:outline-none focus:ring-4 focus:ring-teal-700/20 disabled:cursor-wait disabled:opacity-60">
                                <span>Submit application</span>
                                <svg class="size-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m9 18 6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </button>
                        </form>
                    </div>
                </section>
            </main>

            <section id="result-panel" class="hidden pb-10" aria-live="polite">
                <div class="rounded-[1.75rem] bg-slate-950 p-5 text-white shadow-2xl shadow-slate-950/20 sm:p-7">
                    <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-center gap-4">
                            <span id="result-icon" class="grid size-12 place-items-center rounded-2xl bg-white/10 text-teal-300"></span>
                            <div>
                                <p id="result-eyebrow" class="text-xs font-semibold uppercase tracking-[0.16em] text-teal-300">Application ready</p>
                                <h2 id="result-title" class="mt-1 text-2xl font-semibold tracking-tight">Submitted</h2>
                                <p id="result-id" class="mt-1 font-mono text-xs text-slate-400"></p>
                            </div>
                        </div>
                        <button id="process-loan" type="button" class="rounded-xl bg-[#d9f99d] px-5 py-3 text-sm font-semibold text-slate-950 transition hover:-translate-y-0.5 hover:bg-lime-200 focus:outline-none focus:ring-4 focus:ring-lime-200/20 disabled:cursor-not-allowed disabled:opacity-50">
                            Run decision workflow
                        </button>
                    </div>

                    <div class="mt-7 grid gap-6 border-t border-white/10 pt-6 lg:grid-cols-[0.7fr_1.3fr]">
                        <div id="loan-summary" class="grid grid-cols-2 gap-3"></div>
                        <div>
                            <div class="flex items-center justify-between gap-3">
                                <h3 class="text-sm font-semibold">Decision trail</h3>
                                <span id="history-count" class="text-xs text-slate-500"></span>
                            </div>
                            <ol id="history-list" class="mt-4 grid gap-2"></ol>
                        </div>
                    </div>
                </div>
            </section>

            <footer class="flex items-center justify-between gap-4 border-t border-slate-900/10 py-5 text-xs text-slate-500">
                <p>BankFlow <span class="text-slate-300">/</span> Transparent loan processing</p>
                <a href="{{ route('health') }}" class="transition hover:text-teal-800">Service health</a>
            </footer>
        </div>
    </body>
</html>
