<?php

namespace App\Support;

use App\Models\Holiday;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class SchoolCalendar
{
    public static function holidayOn(CarbonInterface|string $date): ?Holiday
    {
        $dateString = Carbon::parse($date)->toDateString();

        return Holiday::query()
            ->where('is_pjj', false)
            ->whereDate('tanggal', '<=', $dateString)
            ->where(function ($query) use ($dateString) {
                $query->whereNull('tanggal_akhir')
                    ->whereDate('tanggal', $dateString)
                    ->orWhereDate('tanggal_akhir', '>=', $dateString);
            })
            ->first();
    }

    public static function pjjOn(CarbonInterface|string $date): ?Holiday
    {
        $dateString = Carbon::parse($date)->toDateString();

        return Holiday::query()
            ->where('is_pjj', true)
            ->whereDate('tanggal', '<=', $dateString)
            ->where(function ($query) use ($dateString) {
                $query->whereNull('tanggal_akhir')
                    ->whereDate('tanggal', $dateString)
                    ->orWhereDate('tanggal_akhir', '>=', $dateString);
            })
            ->first();
    }

    public static function isPjj(CarbonInterface|string $date): bool
    {
        return self::pjjOn($date) !== null;
    }

    public static function isHoliday(CarbonInterface|string $date): bool
    {
        $holiday = self::holidayOn($date);

        return $holiday !== null && self::holidayStartTime($holiday, $date) === '00:00:00';
    }

    public static function isHolidayAt(CarbonInterface|string $date, string $time): bool
    {
        $holiday = self::holidayOn($date);

        if (!$holiday) {
            return false;
        }

        return $time >= self::holidayStartTime($holiday, $date);
    }

    public static function holidayMessage(CarbonInterface|string $date): ?string
    {
        $holiday = self::holidayOn($date);

        if (!$holiday) {
            return null;
        }

        $message = 'Tanggal ini ditetapkan sebagai hari libur'
            . ($holiday->keterangan ? ': ' . $holiday->keterangan : '');
        $holidayStartTime = self::holidayStartTime($holiday, $date);

        if ($holidayStartTime !== '00:00:00') {
            $message .= ' mulai pukul ' . substr($holidayStartTime, 0, 5);
        }

        $endDate = $holiday->tanggal_akhir ?? $holiday->tanggal;

        if ($holiday->tanggal->toDateString() !== $endDate->toDateString()) {
            $message .= ' (' . $holiday->tanggal->format('d-m-Y')
                . ' s.d. ' . $endDate->format('d-m-Y') . ')';
        }

        return $message . '.';
    }

    private static function holidayStartTime(Holiday $holiday, CarbonInterface|string $date): string
    {
        $dateString = Carbon::parse($date)->toDateString();

        if ($dateString !== Carbon::parse($holiday->tanggal)->toDateString()) {
            return '00:00:00';
        }

        return (string) ($holiday->jam_mulai_libur ?? '00:00:00');
    }

    public static function effectiveDayCounts(CarbonInterface $startDate, CarbonInterface $endDate): array
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->startOfDay();
        $holidays = Holiday::query()
            ->whereDate('tanggal', '<=', $end->toDateString())
            ->where(function ($query) use ($start) {
                $query->whereNull('tanggal_akhir')
                    ->whereDate('tanggal', '>=', $start->toDateString())
                    ->orWhereDate('tanggal_akhir', '>=', $start->toDateString());
            })
            ->get(['tanggal', 'tanggal_akhir', 'jam_mulai_libur', 'keterangan', 'is_pjj']);

        $effectiveDays = 0;
        $holidayWeekdays = 0;

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            if ($date->isWeekend()) {
                continue;
            }

            $isHoliday = $holidays->contains(function (Holiday $holiday) use ($date) {
                if ($holiday->is_pjj) {
                    return false;
                }

                $holidayStart = Carbon::parse($holiday->tanggal)->startOfDay();
                $holidayEnd = Carbon::parse($holiday->tanggal_akhir ?? $holiday->tanggal)->startOfDay();

                return $date->betweenIncluded($holidayStart, $holidayEnd)
                    && (
                        !$date->isSameDay($holidayStart)
                        || ($holiday->jam_mulai_libur ?? '00:00:00') === '00:00:00'
                    );
            });

            if ($isHoliday) {
                $holidayWeekdays++;
                continue;
            }

            $effectiveDays++;
        }

        return [
            'effective_days' => $effectiveDays,
            'holiday_weekdays' => $holidayWeekdays,
        ];
    }
}
