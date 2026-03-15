<div class="min-h-screen">
    <div class="mx-auto flex min-h-screen max-w-7xl flex-col gap-6 px-4 py-6 lg:px-6">
        <header class="rounded-3xl border border-white/10 bg-zinc-900/80 p-6 shadow-2xl shadow-black/30 backdrop-blur">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="space-y-2">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-[var(--color-accent)]">Cherokee Lexicon</p>
                    <h1 class="text-3xl font-semibold tracking-tight text-white">Word Editor</h1>
                    <p class="max-w-2xl text-sm text-zinc-400">
                        Browse and update Cherokee words with an English gloss, pronunciation guide, and syllabary form.
                        Manual edits stay in the database and are protected from seed imports.
                    </p>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row">
                    <label for="search" class="relative block">
                        <span class="sr-only">Search words</span>
                        <input
                            id="search"
                            wire:model.live.debounce.250ms="search"
                            type="text"
                            placeholder="Search English, pronunciation, or Cherokee"
                            class="w-full min-w-72 rounded-2xl border border-white/10 bg-zinc-950 px-4 py-3 text-sm text-white outline-none transition focus:border-[var(--color-accent)] focus:ring-2 focus:ring-[var(--color-accent)]/20"
                        >
                    </label>

                    <button
                        wire:click="createNew"
                        wire:loading.attr="disabled"
                        type="button"
                        class="rounded-2xl bg-[var(--color-accent)] px-4 py-3 text-sm font-medium text-[var(--color-accent-foreground)] transition hover:brightness-110 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        New word
                    </button>
                </div>
            </div>
        </header>

        <div class="grid flex-1 gap-6 lg:grid-cols-[minmax(0,1.2fr)_minmax(22rem,0.8fr)]">
            <section class="rounded-3xl border border-white/10 bg-zinc-900/80 p-4 shadow-2xl shadow-black/30 backdrop-blur">
                <div class="mb-4 flex items-center justify-between px-2">
                    <div>
                        <h2 class="text-lg font-medium text-white">Words</h2>
                        <p class="text-sm text-zinc-400">English, pronunciation, and Cherokee syllabary.</p>
                    </div>
                </div>

                <div class="overflow-hidden rounded-2xl border border-white/10">
                    <div class="hidden grid-cols-[minmax(0,1.1fr)_minmax(0,1fr)_minmax(0,1fr)_auto] gap-3 border-b border-white/10 bg-zinc-950/80 px-4 py-3 text-xs font-semibold uppercase tracking-[0.18em] text-zinc-500 md:grid">
                        <span>English</span>
                        <span>Pronunciation</span>
                        <span>Cherokee</span>
                        <span>Status</span>
                    </div>

                    <div class="divide-y divide-white/10">
                        @forelse ($words as $word)
                            <button
                                wire:click="loadWord({{ $word->id }})"
                                type="button"
                                @if($selectedWordId === $word->id) aria-current="true" @endif
                                class="grid w-full gap-3 px-4 py-4 text-left transition hover:bg-white/5 md:grid-cols-[minmax(0,1.1fr)_minmax(0,1fr)_minmax(0,1fr)_auto] {{ $selectedWordId === $word->id ? 'bg-white/5 ring-1 ring-inset ring-[var(--color-accent)]/40' : '' }}"
                            >
                                <div>
                                    <div class="text-xs uppercase tracking-[0.18em] text-zinc-500 md:hidden">English</div>
                                    <div class="font-medium text-white">{{ $word->english }}</div>
                                    <div class="mt-1 text-xs text-zinc-500">{{ $word->category }}</div>
                                </div>

                                <div>
                                    <div class="text-xs uppercase tracking-[0.18em] text-zinc-500 md:hidden">Pronunciation</div>
                                    <div class="text-zinc-200">{{ $word->transliteration ?: '—' }}</div>
                                </div>

                                <div>
                                    <div class="text-xs uppercase tracking-[0.18em] text-zinc-500 md:hidden">Cherokee</div>
                                    <div class="text-lg text-[var(--color-accent)]">{{ $word->cherokee }}</div>
                                </div>

                                <div class="flex items-start justify-start md:justify-end">
                                    @if ($word->is_user_modified)
                                        <span class="rounded-full border border-[var(--color-accent)]/30 bg-[color:var(--color-accent)]/10 px-2.5 py-1 text-xs font-medium text-[var(--color-accent)]">
                                            Edited
                                        </span>
                                    @else
                                        <span class="rounded-full border border-white/10 bg-white/5 px-2.5 py-1 text-xs font-medium text-zinc-400">
                                            Seed
                                        </span>
                                    @endif
                                </div>
                            </button>
                        @empty
                            <div class="px-4 py-10 text-center text-sm text-zinc-400">
                                No words matched your search.
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="mt-4">
                    {{ $words->links() }}
                </div>
            </section>

            <section wire:loading.class="opacity-50 pointer-events-none" wire:target="loadWord, createNew" class="rounded-3xl border border-white/10 bg-zinc-900/80 p-6 shadow-2xl shadow-black/30 backdrop-blur transition-opacity">
                <div class="mb-6 flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-medium text-white">
                            {{ $selectedWordId ? 'Edit word' : 'Create word' }}
                        </h2>
                        <p class="mt-1 text-sm text-zinc-400">
                            Focus on the three main fields first: English, pronunciation, and Cherokee syllabary.
                        </p>
                    </div>

                    @if ($statusMessage !== '')
                        <div class="rounded-full border border-[var(--color-accent)]/30 bg-[color:var(--color-accent)]/10 px-3 py-1 text-xs font-medium text-[var(--color-accent)]">
                            {{ $statusMessage }}
                        </div>
                    @endif
                </div>

                <form wire:submit="save" class="space-y-5">
                    <div class="space-y-2">
                        <label for="english" class="text-sm font-medium text-zinc-200">English</label>
                        <input
                            wire:model="english"
                            id="english"
                            type="text"
                            class="w-full rounded-2xl border border-white/10 bg-zinc-950 px-4 py-3 text-sm text-white outline-none transition focus:border-[var(--color-accent)] focus:ring-2 focus:ring-[var(--color-accent)]/20"
                        >
                        @error('english') <p class="text-sm text-rose-400">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="transliteration" class="text-sm font-medium text-zinc-200">Pronunciation</label>
                        <input
                            wire:model="transliteration"
                            id="transliteration"
                            type="text"
                            placeholder="Example: Shi-yo"
                            class="w-full rounded-2xl border border-white/10 bg-zinc-950 px-4 py-3 text-sm text-white outline-none transition focus:border-[var(--color-accent)] focus:ring-2 focus:ring-[var(--color-accent)]/20"
                        >
                        @error('transliteration') <p class="text-sm text-rose-400">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="cherokee" class="text-sm font-medium text-zinc-200">Cherokee</label>
                        <textarea
                            wire:model="cherokee"
                            id="cherokee"
                            rows="3"
                            class="w-full rounded-2xl border border-white/10 bg-zinc-950 px-4 py-3 text-lg text-[var(--color-accent)] outline-none transition focus:border-[var(--color-accent)] focus:ring-2 focus:ring-[var(--color-accent)]/20"
                        ></textarea>
                        @error('cherokee') <p class="text-sm text-rose-400">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid gap-5 md:grid-cols-2">
                        <div class="space-y-2">
                            <label for="category" class="text-sm font-medium text-zinc-200">Category</label>
                            <input
                                wire:model="category"
                                id="category"
                                type="text"
                                class="w-full rounded-2xl border border-white/10 bg-zinc-950 px-4 py-3 text-sm text-white outline-none transition focus:border-[var(--color-accent)] focus:ring-2 focus:ring-[var(--color-accent)]/20"
                            >
                            @error('category') <p class="text-sm text-rose-400">{{ $message }}</p> @enderror
                        </div>

                        <div class="space-y-2">
                            <label for="confidence" class="text-sm font-medium text-zinc-200">Confidence</label>
                            <select
                                wire:model="confidence"
                                id="confidence"
                                class="w-full rounded-2xl border border-white/10 bg-zinc-950 px-4 py-3 text-sm text-white outline-none transition focus:border-[var(--color-accent)] focus:ring-2 focus:ring-[var(--color-accent)]/20"
                            >
                                <option value="high">high</option>
                                <option value="medium">medium</option>
                                <option value="needs_review">needs_review</option>
                            </select>
                            @error('confidence') <p class="text-sm text-rose-400">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label for="dialect" class="text-sm font-medium text-zinc-200">Dialect</label>
                        <input
                            wire:model="dialect"
                            id="dialect"
                            type="text"
                            class="w-full rounded-2xl border border-white/10 bg-zinc-950 px-4 py-3 text-sm text-white outline-none transition focus:border-[var(--color-accent)] focus:ring-2 focus:ring-[var(--color-accent)]/20"
                        >
                        @error('dialect') <p class="text-sm text-rose-400">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="notes" class="text-sm font-medium text-zinc-200">Notes</label>
                        <textarea
                            wire:model="notes"
                            id="notes"
                            rows="4"
                            class="w-full rounded-2xl border border-white/10 bg-zinc-950 px-4 py-3 text-sm text-white outline-none transition focus:border-[var(--color-accent)] focus:ring-2 focus:ring-[var(--color-accent)]/20"
                        ></textarea>
                        @error('notes') <p class="text-sm text-rose-400">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
                        <button
                            wire:click="createNew"
                            wire:loading.attr="disabled"
                            type="button"
                            class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm font-medium text-zinc-200 transition hover:bg-white/10 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Clear
                        </button>

                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            class="rounded-2xl bg-[var(--color-accent)] px-4 py-3 text-sm font-medium text-[var(--color-accent-foreground)] transition hover:brightness-110 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                        >
                            <svg wire:loading wire:target="save" class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span>{{ $selectedWordId ? 'Save changes' : 'Create word' }}</span>
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </div>
</div>
