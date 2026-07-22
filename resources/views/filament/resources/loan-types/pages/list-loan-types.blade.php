<x-filament-panels::page>
    <div class="flex flex-col gap-6">
        <section class="bankflow-hero">
            <div class="bankflow-orb"></div>

            <div class="relative flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-2xl">
                    <div class="mb-4 inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-semibold tracking-wide text-teal-100">
                        <x-filament::icon icon="heroicon-o-sparkles" class="size-4" />
                        PRODUCT CONTROL CENTER
                    </div>
                    <h2 class="text-2xl font-semibold tracking-tight sm:text-3xl">Shape every lending journey from one place.</h2>
                    <p class="mt-3 max-w-xl text-sm leading-6 text-teal-100/80 sm:text-base">
                        Keep product availability, workflow readiness, and live application volume visible before you make a change.
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur-sm">
                        <div class="text-2xl font-semibold">{{ $this->loanTypeCards->where('is_active', true)->count() }}</div>
                        <div class="text-teal-100/70">Active products</div>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur-sm">
                        <div class="text-2xl font-semibold">{{ $this->loanTypeCards->sum('open_loans_count') }}</div>
                        <div class="text-teal-100/70">Open applications</div>
                    </div>
                </div>
            </div>
        </section>

        <section aria-labelledby="product-overview-heading">
            <div class="mb-4 flex items-end justify-between gap-4">
                <div>
                    <h2 id="product-overview-heading" class="text-lg font-semibold text-gray-950 dark:text-white">Product overview</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Readiness and demand at a glance.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
                @forelse ($this->loanTypeCards as $loanType)
                    @php($publishedWorkflow = $loanType->workflowConfigurations->first())

                    <article class="bankflow-type-card">
                        <div class="absolute inset-x-0 top-0 h-1 {{ $loanType->is_active ? 'bg-teal-500' : 'bg-gray-300 dark:bg-gray-700' }}"></div>

                        <div class="flex items-start justify-between gap-4">
                            <div class="flex min-w-0 items-center gap-3">
                                <div class="flex size-11 shrink-0 items-center justify-center rounded-2xl {{ $loanType->is_active ? 'bg-teal-50 text-teal-700 dark:bg-teal-500/10 dark:text-teal-300' : 'bg-gray-100 text-gray-500 dark:bg-white/5 dark:text-gray-400' }}">
                                    <x-filament::icon icon="heroicon-o-building-library" class="size-6" />
                                </div>
                                <div class="min-w-0">
                                    <h3 class="truncate font-semibold text-gray-950 dark:text-white">{{ $loanType->name }}</h3>
                                    <p class="mt-0.5 font-mono text-xs text-gray-500 dark:text-gray-400">{{ $loanType->code }}</p>
                                </div>
                            </div>

                            <x-filament::badge :color="$loanType->is_active ? 'success' : 'gray'">
                                {{ $loanType->is_active ? 'Active' : 'Paused' }}
                            </x-filament::badge>
                        </div>

                        <div class="mt-5 grid grid-cols-3 gap-2">
                            <div class="bankflow-metric">
                                <div class="text-lg font-semibold text-gray-950 dark:text-white">{{ number_format($loanType->loans_count) }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Applications</div>
                            </div>
                            <div class="bankflow-metric">
                                <div class="text-lg font-semibold text-gray-950 dark:text-white">{{ number_format($loanType->open_loans_count) }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Open</div>
                            </div>
                            <div class="bankflow-metric">
                                <div class="text-lg font-semibold text-gray-950 dark:text-white">{{ $publishedWorkflow?->steps_count ?? 0 }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Stages</div>
                            </div>
                        </div>

                        <div class="mt-4 flex items-center justify-between gap-3 rounded-xl border border-gray-100 px-3 py-2.5 dark:border-white/5">
                            <div class="flex min-w-0 items-center gap-2">
                                <span class="size-2 shrink-0 rounded-full {{ $publishedWorkflow ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                                <div class="min-w-0 text-sm">
                                    @if ($publishedWorkflow)
                                        <span class="truncate text-gray-700 dark:text-gray-300">{{ $publishedWorkflow->name }}</span>
                                        <span class="text-gray-400"> · v{{ $publishedWorkflow->version }}</span>
                                    @else
                                        <span class="text-amber-700 dark:text-amber-300">No published workflow</span>
                                    @endif
                                </div>
                            </div>
                            <span class="shrink-0 text-xs text-gray-400">{{ $loanType->updated_at->diffForHumans() }}</span>
                        </div>

                        <div class="mt-5 flex items-center justify-end gap-2">
                            <x-filament::button
                                :href="\App\Filament\Resources\LoanTypes\LoanTypeResource::getUrl('view', ['record' => $loanType])"
                                tag="a"
                                color="gray"
                                size="sm"
                                icon="heroicon-o-eye"
                            >
                                Details
                            </x-filament::button>
                            <x-filament::button
                                :href="\App\Filament\Resources\LoanTypes\LoanTypeResource::getUrl('edit', ['record' => $loanType])"
                                tag="a"
                                size="sm"
                                icon="heroicon-o-adjustments-horizontal"
                            >
                                Manage
                            </x-filament::button>
                        </div>
                    </article>
                @empty
                    <div class="col-span-full rounded-2xl border border-dashed border-gray-300 p-10 text-center dark:border-white/15">
                        <x-filament::icon icon="heroicon-o-building-library" class="mx-auto size-10 text-gray-400" />
                        <h3 class="mt-4 font-semibold text-gray-950 dark:text-white">No loan types yet</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Create the first product, then attach and publish its workflow.</p>
                    </div>
                @endforelse
            </div>
        </section>

        <section aria-labelledby="product-directory-heading">
            <div class="mb-4">
                <h2 id="product-directory-heading" class="text-lg font-semibold text-gray-950 dark:text-white">Product directory</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Search, filter, and make controlled availability changes.</p>
            </div>

            {{ $this->table }}
        </section>
    </div>
</x-filament-panels::page>
