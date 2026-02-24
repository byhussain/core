<?php

namespace SmartTill\Core\Filament\Resources\Sales\Widgets;

use Carbon\CarbonPeriod;
use Filament\Facades\Filament;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
use SmartTill\Core\Enums\SaleStatus;
use SmartTill\Core\Filament\Concerns\FormatsCurrency;
use SmartTill\Core\Filament\Resources\Helpers\ResourceCanAccessHelper;
use SmartTill\Core\Models\Sale;

class SaleStats extends BaseWidget
{
    use FormatsCurrency;

    public static function canView(): bool
    {
        return ResourceCanAccessHelper::check('View Sales Stats Widget');
    }

    protected function getStats(): array
    {
        $store = Filament::getTenant();
        $today = Carbon::today();
        $now = Carbon::now();
        $yesterday = $today->copy()->subDay();
        $startOfMonth = $now->copy()->startOfMonth();
        $startOfLastMonth = $now->copy()->subMonth()->startOfMonth();
        $endOfLastMonth = $now->copy()->subMonth()->endOfMonth();

        // Base query with store filter
        $baseQuery = fn () => Sale::where('store_id', $store->id)
            ->where('status', SaleStatus::Completed);

        // Today's sales
        $todayTotal = $baseQuery()
            ->whereDate('created_at', $today)
            ->sum('total');
        $yesterdayTotal = $baseQuery()
            ->whereDate('created_at', $yesterday)
            ->sum('total');
        $todayChange = $yesterdayTotal > 0 ? (($todayTotal - $yesterdayTotal) / $yesterdayTotal) * 100 : null;
        $todayIcon = $todayChange === null ? null : ($todayChange >= 0 ? Heroicon::ArrowTrendingUp : Heroicon::ArrowTrendingDown);
        $todayColor = $todayChange === null ? null : ($todayChange >= 0 ? 'success' : 'danger');
        // Generate daily sales totals for the last 7 days (optimized)
        $start7 = $today->copy()->subDays(6);
        $sales7 = $baseQuery()
            ->whereBetween('created_at', [$start7, $now])
            ->selectRaw('DATE(created_at) as date, SUM(total) as total')
            ->groupBy('date')
            ->pluck('total', 'date');
        $period7 = CarbonPeriod::create($start7, $now);
        $todayChart = collect($period7)->map(fn ($date) => $sales7[$date->toDateString()] ?? 0)->toArray();

        // Monthly sales
        $monthTotal = $baseQuery()
            ->whereMonth('created_at', $now->month)
            ->whereYear('created_at', $now->year)
            ->sum('total');
        $lastMonthTotal = $baseQuery()
            ->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])
            ->sum('total');
        $monthChange = $lastMonthTotal > 0 ? (($monthTotal - $lastMonthTotal) / $lastMonthTotal) * 100 : null;
        $monthIcon = $monthChange === null ? null : ($monthChange >= 0 ? Heroicon::ArrowTrendingUp : Heroicon::ArrowTrendingDown);
        $monthColor = $monthChange === null ? null : ($monthChange >= 0 ? 'success' : 'danger');
        // Generate daily sales totals for the current month (optimized)
        $salesMonth = $baseQuery()
            ->whereBetween('created_at', [$startOfMonth, $now])
            ->selectRaw('DATE(created_at) as date, SUM(total) as total')
            ->groupBy('date')
            ->pluck('total', 'date');
        $periodMonth = CarbonPeriod::create($startOfMonth, $now);
        $monthChart = collect($periodMonth)->map(fn ($date) => $salesMonth[$date->toDateString()] ?? 0)->toArray();

        // Active days in the current month (days with at least one completed sale) - optimized
        // Use COUNT(DISTINCT) to count unique days, not total rows
        $activeDays = $baseQuery()
            ->whereMonth('created_at', $now->month)
            ->whereYear('created_at', $now->year)
            ->selectRaw('COUNT(DISTINCT DATE(created_at)) as count')
            ->value('count') ?? 0;
        // Active days in the previous month (days with at least one completed sale) - optimized
        $lastMonthActiveDays = $baseQuery()
            ->whereMonth('created_at', $startOfLastMonth->month)
            ->whereYear('created_at', $startOfLastMonth->year)
            ->selectRaw('COUNT(DISTINCT DATE(created_at)) as count')
            ->value('count') ?? 0;

        // Today's sales count
        $todayCount = $baseQuery()
            ->whereDate('created_at', $today)
            ->count();
        // Monthly sales count
        $monthCount = $baseQuery()
            ->whereMonth('created_at', $now->month)
            ->whereYear('created_at', $now->year)
            ->count();
        $lastMonthCount = $baseQuery()
            ->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])
            ->count();
        // Per day sales count (active days only)
        $perDayAmount = $activeDays > 0 ? $monthTotal / $activeDays : 0;
        $perDayCount = $activeDays > 0 ? $monthCount / $activeDays : 0;
        // Last month's per day sales count (active days only)
        $lastMonthPerDayCount = $lastMonthActiveDays > 0 ? $lastMonthCount / $lastMonthActiveDays : 0;
        $perDayCountChange = $lastMonthPerDayCount > 0 ? (($perDayCount - $lastMonthPerDayCount) / $lastMonthPerDayCount) * 100 : null;
        $perDayCountIcon = $perDayCountChange === null ? null : ($perDayCountChange >= 0 ? Heroicon::ArrowTrendingUp : Heroicon::ArrowTrendingDown);
        $perDayCountColor = $perDayCountChange === null ? null : ($perDayCountChange >= 0 ? 'success' : 'danger');

        $todayTotalAmount = $this->convertFromStorage($todayTotal, $store);
        $monthTotalAmount = $this->convertFromStorage($monthTotal, $store);
        $perDayAmountValue = $this->convertFromStorage($perDayAmount, $store);

        $todayChart = collect($period7)
            ->map(fn ($date) => $this->convertFromStorage($sales7[$date->toDateString()] ?? 0, $store))
            ->toArray();
        $monthChart = collect($periodMonth)
            ->map(fn ($date) => $this->convertFromStorage($salesMonth[$date->toDateString()] ?? 0, $store))
            ->toArray();

        return [
            Stat::make("Today's Sale", $this->formatCompactCurrency($todayTotalAmount, $store))
                ->description(($todayChange === null ? 'No data' : (abs(round($todayChange, 2)).'% '.($todayChange >= 0 ? 'increase' : 'decrease')))." | $todayCount sales")
                ->descriptionIcon($todayIcon, IconPosition::Before)
                ->color($todayColor)
                ->chart($todayChart),
            Stat::make('Monthly Sale', $this->formatCompactCurrency($monthTotalAmount, $store))
                ->description(($monthChange === null ? 'No data' : (abs(round($monthChange, 2)).'% '.($monthChange >= 0 ? 'increase' : 'decrease')))." | $monthCount sales")
                ->descriptionIcon($monthIcon, IconPosition::Before)
                ->color($monthColor)
                ->chart($monthChart),
            Stat::make('Per Day Average', $this->formatCompactCurrency($perDayAmountValue, $store))
                ->description(($perDayCountChange === null ? 'No data' : (abs(round($perDayCountChange, 2)).'% '.($perDayCountChange >= 0 ? 'increase' : 'decrease'))).' | '.round($perDayCount).' sales/active day')
                ->descriptionIcon($perDayCountIcon, IconPosition::Before)
                ->color($perDayCountColor)
                ->chart($monthChart),
        ];
    }
}
