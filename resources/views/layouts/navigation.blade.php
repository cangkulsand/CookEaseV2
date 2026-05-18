<nav x-data="{ open: false }" class="bg-white border-b border-gray-100 dark:border-gray-100">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center space-x-2">
                    <a href="{{ route('dashboard') }}" class="flex items-center">
                        <img src="{{ asset('images/logo.png') }}" alt="CookEase Logo" class="h-9 w-auto">
                        <span class="ml-2 text-xl font-semibold text-gray-800 dark:text-dark">CookEase</span>
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>
                    <x-nav-link :href="route('generate')" :active="request()->routeIs('generate')">
                        {{ __('Generate Recipe') }}
                    </x-nav-link>
                    <x-nav-link :href="route('recipes.browse')" :active="request()->routeIs('recipes.browse')">
                        {{ __('Browse Recipes') }}
                    </x-nav-link>
                    <x-nav-link :href="route('recipes.saved')" :active="request()->routeIs('recipes.saved')">
                        {{ __('Saved Recipes') }}
                    </x-nav-link>
                    <x-nav-link :href="route('meal-plan.index')" :active="request()->routeIs('meal-plan.index')">
                        {{ __('Meal Plans') }}
                    </x-nav-link>
                </div>
            </div>

            <!-- Notification & Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6 space-x-4">
            @auth
                <!-- Notifications Dropdown -->
                <div class="relative mr-2" x-data="{ openNotif: false }">
                    <button @click="openNotif = !openNotif"
                        class="relative inline-flex items-center px-3 py-2 text-sm font-medium text-gray-600 bg-white rounded-full hover:bg-gray-100 focus:outline-none transition duration-150">
                        <span>🔔</span>

                        @php $count = auth()->user()->unreadNotifications->count(); @endphp
                        @if ($count > 0)
                            <span class="absolute top-0 right-0 inline-flex items-center justify-center px-1.5 py-0.5 text-xs font-bold text-white bg-danger-500 rounded-full transform translate-x-1/2 -translate-y-1/2">
                                {{ $count }}
                            </span>
                        @endif
                    </button>

                    <div x-show="openNotif" @click.away="openNotif = false"
                        x-transition
                        class="absolute right-0 mt-2 w-80 bg-white rounded-xl shadow-xl border border-gray-200 overflow-hidden z-50 max-h-80 overflow-y-auto">
                        <div class="px-4 py-3 border-b font-semibold text-gray-700 bg-gray-50">
                            Notifications
                        </div>
                        <ul class="divide-y divide-gray-200">
                            @forelse (auth()->user()->unreadNotifications as $notification)
                            <li class="px-4 py-3 hover:bg-gray-50 transition">
                                <div class="flex justify-between items-start">
                                    {{-- Make message clickable --}}
                                    <a href="{{ $notification->data['route'] }}"
                                    class="text-sm text-gray-900 leading-snug">
                                    {!! $notification->data['message'] !!}
                                    </a>

                                    {{-- Mark as read --}}
                                    <form method="POST" action="{{ route('notifications.mark', $notification->id) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"
                                            class="text-xs text-info-600 hover:underline ml-2 mt-0.5">
                                            Mark
                                        </button>
                                    </form>
                                </div>
                            </li>
                            @empty
                            <li class="px-4 py-4 text-sm text-gray-500 text-center">
                                No new notifications ✨
                            </li>
                            @endforelse

                        </ul>
                    </div>
                </div>
            @endauth

                <!-- Settings Dropdown -->
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 dark:text-gray-400 bg-white hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name }}</div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-primary-400 hover:text-primary-500 hover:bg-primary-100 focus:outline-none focus:bg-primary-100 focus:text-primary-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('generate')" :active="request()->routeIs('generate')">
                {{ __('Generate Recipe') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('recipes.browse')" :active="request()->routeIs('recipes.browse')">
                {{ __('Browse Recipes') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('recipes.saved')" :active="request()->routeIs('recipes.saved')">
                {{ __('Saved Recipes') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('meal-plan.index')" :active="request()->routeIs('meal-plan.index')">
                {{ __('Meal Plans') }}
            </x-responsive-nav-link>
        </div>

        <!-- Responsive Notifications -->
        @auth
        <div class="border-t border-gray-200 dark:border-gray-600 pt-4 pb-1 px-4">
            <div class="mb-2 font-semibold text-gray-700 dark:text-gray-300">Notifications ({{ auth()->user()->unreadNotifications->count() }})</div>
            <ul class="space-y-2 max-h-48 overflow-auto">
                @forelse (auth()->user()->unreadNotifications as $notification)
                    <li class="text-gray-600 dark:text-gray-400">
                        {{ $notification->data['message'] }}
                        <form method="POST" action="{{ route('notifications.mark', $notification->id) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="text-info-500 hover:underline text-xs mt-1">Mark as read</button>
                        </form>
                    </li>
                @empty
                    <li class="text-gray-500 dark:text-gray-400">No new notifications.</li>
                @endforelse
            </ul>
        </div>
        @endauth

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200 dark:border-gray-600">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800 dark:text-gray-200">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
