<div class="relative flex min-h-screen flex-col items-center justify-center p-6 lg:p-8">

    <!-- Background subtle gradient effect (optional) -->
    <div class="fixed inset-0 pointer-events-none bg-[radial-gradient(circle_at_center,var(--color-accent)_0%,transparent_15%)] opacity-[0.03]"></div>

    <!-- Centerpiece Glass Card -->
    <div class="w-full max-w-[400px] z-10 transition-opacity opacity-100 duration-750 starting:opacity-0 starting:translate-y-4">

        <div class="text-center mb-2">
            <a href="{{ url('/') }}" class="inline-block focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)] focus:ring-offset-2 focus:ring-offset-zinc-950 rounded mb-4" aria-label="deceptio.ai – Home">
                <img src="{{ asset('images/logo-header.png') }}" alt="" class="mx-auto h-12 w-auto sm:h-14" width="240" height="48">
            </a>
        </div>

        <main class="rounded-3xl border border-white/10 bg-zinc-900/80 p-8 shadow-2xl shadow-black/30 backdrop-blur w-full">

            <form wire:submit="login" class="space-y-5">

                <div class="space-y-2">
                    <input
                        wire:model="email"
                        id="email"
                        placeholder="Email"
                        type="email"
                        autofocus
                        class="w-full rounded-xl border border-white/10 bg-zinc-950 px-6 py-4 text-sm text-white outline-none transition focus:border-[var(--color-accent)] focus:ring-2 focus:ring-[var(--color-accent)]/20"
                    >
                    @error('email') <p class="text-sm text-rose-400">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-2">
                    <input
                        wire:model="password"
                        id="password"
                        type="password"
                        placeholder="Password"
                        class="w-full rounded-xl border border-white/10 bg-zinc-950 px-6 py-4 text-sm text-white outline-none transition focus:border-[var(--color-accent)] focus:ring-2 focus:ring-[var(--color-accent)]/20"
                    >
                    @error('password') <p class="text-sm text-rose-400">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-center w-full rounded-xl border border-white/10 bg-zinc-950 px-6 py-4 transition focus-within:border-[var(--color-accent)] focus-within:ring-2 focus-within:ring-[var(--color-accent)]/20">
                    <label for="remember" class="flex items-center gap-3 cursor-pointer w-full">
                        <input
                            wire:model="remember"
                            id="remember"
                            type="checkbox"
                            class="rounded border-white/10 bg-zinc-900 text-[var(--color-accent)] focus:ring-0 focus:ring-offset-0"
                        >
                        <span class="text-sm text-zinc-400">Remember me</span>
                    </label>
                </div>

                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    class="w-full rounded-2xl bg-[var(--color-accent)] px-4 py-3 text-sm font-medium text-[var(--color-accent-foreground)] transition hover:brightness-110 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 mt-4"
                >
                    <svg wire:loading wire:target="login" class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    <span>Sign In</span>
                </button>
            </form>

        </main>
    </div>
</div>
