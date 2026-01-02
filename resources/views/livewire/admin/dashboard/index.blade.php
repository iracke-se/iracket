<div class="max-w-7xl mx-auto"
    x-data="{
        charts: {},
        init() {
            this.loadChartJS().then(() => {
                this.initCharts();
            });
        },
        loadChartJS() {
            return new Promise((resolve) => {
                if (typeof Chart !== 'undefined') {
                    resolve();
                    return;
                }
                const script = document.createElement('script');
                script.src = 'https://cdn.jsdelivr.net/npm/chart.js';
                script.onload = resolve;
                document.head.appendChild(script);
            });
        },
        destroyCharts() {
            Object.values(this.charts).forEach(chart => {
                if (chart) chart.destroy();
            });
            this.charts = {};
        },
        initCharts() {
            const userChartCanvas = document.getElementById('userGrowthChart');
            if (!userChartCanvas) return;

            this.destroyCharts();

            const isDark = document.documentElement.classList.contains('dark');
            Chart.defaults.color = isDark ? '#a1a1aa' : '#71717a';
            Chart.defaults.borderColor = isDark ? '#3f3f46' : '#e4e4e7';

            // User Growth Chart
            this.charts.userGrowth = new Chart(document.getElementById('userGrowthChart').getContext('2d'), {
                type: 'line',
                data: {
                    labels: @js($monthlyUsers->pluck('month')),
                    datasets: [{
                        label: 'Users',
                        data: @js($monthlyUsers->pluck('count')),
                        borderColor: '#34C759',
                        backgroundColor: 'rgba(52, 199, 89, 0.1)',
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
                }
            });

            // Gender Distribution Chart
            this.charts.gender = new Chart(document.getElementById('genderChart').getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: ['{{ __('admin.male') }}', '{{ __('admin.female') }}', '{{ __('admin.other') }}', '{{ __('admin.not_specified') }}'],
                    datasets: [{
                        data: [{{ $genderDistribution['male'] }}, {{ $genderDistribution['female'] }}, {{ $genderDistribution['other'] }}, {{ $genderDistribution['unknown'] }}],
                        backgroundColor: ['#3b82f6', '#ec4899', '#8b5cf6', '#6b7280'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'right', labels: { padding: 20, usePointStyle: true } } }
                }
            });

            // Match Activity Chart
            this.charts.match = new Chart(document.getElementById('matchChart').getContext('2d'), {
                type: 'bar',
                data: {
                    labels: @js($monthlyMatches->pluck('month')),
                    datasets: [{
                        label: 'Matches',
                        data: @js($monthlyMatches->pluck('count')),
                        backgroundColor: '#8b5cf6',
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
                }
            });
        }
    }"
>
    <h1 class="text-2xl font-bold text-zinc-900 dark:text-white mb-6">{{ __('admin.dashboard') }}</h1>

    <!-- System Health Monitor -->
    @livewire('admin.dashboard.heartbeat-status')

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Users -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl p-6 border border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('admin.total_users') }}</p>
                    <p class="text-3xl font-bold text-zinc-900 dark:text-white mt-1">{{ number_format($totalUsers) }}</p>
                </div>
                <div class="p-3 bg-accent/10 rounded-lg">
                    <svg class="w-6 h-6 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </div>
            </div>
            <div class="mt-4 flex items-center text-sm">
                <span class="text-zinc-500 dark:text-zinc-400">{{ __('admin.this_month') }}</span>
                <span class="ml-2 font-medium text-zinc-900 dark:text-white">{{ $usersThisMonth }}</span>
                @if($userGrowth > 0)
                    <span class="ml-2 text-green-500 dark:text-green-400">+{{ $userGrowth }}%</span>
                @elseif($userGrowth < 0)
                    <span class="ml-2 text-red-500 dark:text-red-400">{{ $userGrowth }}%</span>
                @endif
            </div>
        </div>

        <!-- Total Clubs -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl p-6 border border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('admin.total_clubs') }}</p>
                    <p class="text-3xl font-bold text-zinc-900 dark:text-white mt-1">{{ number_format($totalClubs) }}</p>
                </div>
                <div class="p-3 bg-blue-500/10 rounded-lg">
                    <svg class="w-6 h-6 text-blue-500 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Total Matches -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl p-6 border border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('admin.total_matches') }}</p>
                    <p class="text-3xl font-bold text-zinc-900 dark:text-white mt-1">{{ number_format($totalMatches) }}</p>
                </div>
                <div class="p-3 bg-purple-500/10 rounded-lg">
                    <svg class="w-6 h-6 text-purple-500 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                </div>
            </div>
            <div class="mt-4 flex items-center text-sm">
                <span class="text-zinc-500 dark:text-zinc-400">{{ __('admin.this_month') }}</span>
                <span class="ml-2 font-medium text-zinc-900 dark:text-white">{{ $matchesThisMonth }}</span>
                @if($matchGrowth > 0)
                    <span class="ml-2 text-green-500 dark:text-green-400">+{{ $matchGrowth }}%</span>
                @elseif($matchGrowth < 0)
                    <span class="ml-2 text-red-500 dark:text-red-400">{{ $matchGrowth }}%</span>
                @endif
            </div>
        </div>

        <!-- Active Rate -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl p-6 border border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('admin.verified_users') }}</p>
                    <p class="text-3xl font-bold text-zinc-900 dark:text-white mt-1">
                        {{ $totalUsers > 0 ? round((\App\Models\User::whereNotNull('email_verified_at')->count() / $totalUsers) * 100) : 0 }}%
                    </p>
                </div>
                <div class="p-3 bg-yellow-500/10 rounded-lg">
                    <svg class="w-6 h-6 text-yellow-500 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- User Growth Chart -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl p-6 border border-zinc-200 dark:border-zinc-700">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">{{ __('admin.user_growth') }}</h3>
            <div class="h-64">
                <canvas id="userGrowthChart"></canvas>
            </div>
        </div>

        <!-- Gender Distribution -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl p-6 border border-zinc-200 dark:border-zinc-700">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">{{ __('admin.gender_distribution') }}</h3>
            <div class="h-64 flex items-center justify-center">
                <canvas id="genderChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Match Activity Chart -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl p-6 border border-zinc-200 dark:border-zinc-700 mb-8">
        <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">{{ __('admin.match_activity') }}</h3>
        <div class="h-64">
            <canvas id="matchChart"></canvas>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Users -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700">
            <div class="p-4 border-b border-zinc-200 dark:border-zinc-700">
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ __('admin.recent_users') }}</h3>
            </div>
            <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse($recentUsers as $user)
                    <div class="p-4 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center">
                            <span class="text-sm font-medium text-zinc-600 dark:text-zinc-300">{{ $user->initials() }}</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-zinc-900 dark:text-white truncate">{{ $user->name }}</p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $user->email }}</p>
                        </div>
                        <span class="text-xs text-zinc-400 dark:text-zinc-500">{{ $user->created_at->diffForHumans() }}</span>
                    </div>
                @empty
                    <div class="p-4 text-center text-zinc-500 dark:text-zinc-400">{{ __('admin.no_users_yet') }}</div>
                @endforelse
            </div>
        </div>

        <!-- Recent Matches -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700">
            <div class="p-4 border-b border-zinc-200 dark:border-zinc-700">
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ __('admin.recent_matches') }}</h3>
            </div>
            <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse($recentMatches as $match)
                    <div class="p-4">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm font-medium text-zinc-900 dark:text-white">
                                {{ $match->player1?->name ?? 'Unknown' }} vs {{ $match->player2?->name ?? 'Unknown' }}
                            </span>
                            <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ $match->player1_sets }} - {{ $match->player2_sets }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-accent">{{ $match->winner?->name ?? '-' }}</span>
                            <span class="text-xs text-zinc-400 dark:text-zinc-500">{{ $match->created_at->diffForHumans() }}</span>
                        </div>
                    </div>
                @empty
                    <div class="p-4 text-center text-zinc-500 dark:text-zinc-400">{{ __('admin.no_matches_yet') }}</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

