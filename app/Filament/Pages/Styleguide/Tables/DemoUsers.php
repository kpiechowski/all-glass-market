<?php

declare(strict_types=1);

namespace App\Filament\Pages\Styleguide\Tables;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Static demo rows for the Styleguide table (D-33). Nothing is read from or written to the database:
 * search, list tabs, filters, sorting and pagination are applied to this array in memory.
 */
final class DemoUsers
{
    public const array STATUSES = [
        'active' => ['label' => 'Aktywny', 'color' => 'success'],
        'inactive' => ['label' => 'Nieaktywny', 'color' => 'danger'],
        'invited' => ['label' => 'Zaproszony', 'color' => 'warning'],
    ];

    public const array ACCOUNT_TYPES = [
        'company' => 'Firma',
        'private' => 'Osoba prywatna',
    ];

    /** Columns whose state is another key: the person cell shows `name`. */
    private const array SORT_KEYS = ['person' => 'name'];

    public const array ROLES = [
        'admin' => 'Administrator',
        'seller' => 'Handlowiec',
        'customer' => 'Klient',
    ];

    /**
     * @return array<int, array{name: string, email: string, account_type: string, role: string, status: string, must_change_password: bool, joined_at: string, updated_at: string, orders_count: int}>
     */
    public static function all(): array
    {
        return [
            1 => self::row('Anna Kowalska', 'anna.kowalska@szyby-kowalski.pl', 'company', 'admin', 'active', false, '2024-03-12', '2026-11-04 09:15', 148),
            2 => self::row('Piotr Nowak', 'p.nowak@autoglass.pl', 'company', 'seller', 'active', true, '2024-06-01', '2026-11-03 16:40', 1203),
            3 => self::row('Katarzyna Wiśniewska', 'k.wisniewska@gmail.com', 'private', 'customer', 'active', false, '2025-01-20', '2026-10-30 11:02', 7),
            4 => self::row('Tomasz Wójcik', 'tomasz@wojcik-serwis.pl', 'company', 'customer', 'inactive', false, '2023-11-08', '2026-09-14 08:30', 0),
            5 => self::row('Magdalena Kamińska', 'm.kaminska@onet.pl', 'private', 'customer', 'invited', false, '2026-11-02', '2026-11-02 13:20', 0),
            6 => self::row('Krzysztof Lewandowski', 'k.lewandowski@szybyplus.pl', 'company', 'seller', 'active', false, '2024-09-17', '2026-11-01 10:45', 312),
            7 => self::row('Agnieszka Zielińska', 'agnieszka.zielinska@wp.pl', 'private', 'customer', 'active', true, '2025-04-03', '2026-10-28 17:05', 23),
            8 => self::row('Michał Szymański', 'michal@szymanski-auto.pl', 'company', 'customer', 'inactive', false, '2023-07-22', '2026-06-11 09:00', 56),
            9 => self::row('Ewa Woźniak', 'e.wozniak@glassmarket.pl', 'company', 'admin', 'active', false, '2024-02-29', '2026-11-05 07:55', 2),
            10 => self::row('Paweł Dąbrowski', 'pawel.dabrowski@interia.pl', 'private', 'customer', 'invited', false, '2026-10-31', '2026-10-31 15:10', 0),
            11 => self::row('Joanna Kozłowska', 'j.kozlowska@szyby-poznan.pl', 'company', 'seller', 'active', false, '2025-08-11', '2026-10-25 12:35', 894),
            12 => self::row('Marcin Jankowski', 'marcin.jankowski@gmail.com', 'private', 'customer', 'active', false, '2026-02-14', '2026-10-20 14:50', 4),
        ];
    }

    /**
     * @param  array<string, array<string, mixed>>|null  $filters
     */
    public static function page(
        string $listTab,
        ?string $search,
        ?array $filters,
        ?string $sortColumn,
        ?string $sortDirection,
        int $page,
        int|string $recordsPerPage,
    ): LengthAwarePaginator {
        $records = collect(self::all())
            ->when($listTab !== 'all', fn (Collection $rows): Collection => $rows->where('status', $listTab))
            ->when(filled($search), fn (Collection $rows): Collection => $rows->filter(
                fn (array $row): bool => Str::contains(Str::lower($row['name'].' '.$row['email']), Str::lower((string) $search)),
            ))
            ->when(filled($filters['account_type']['value'] ?? null), fn (Collection $rows): Collection => $rows->where('account_type', $filters['account_type']['value']))
            ->when(filled($filters['role']['value'] ?? null), fn (Collection $rows): Collection => $rows->where('role', $filters['role']['value']))
            ->when((bool) ($filters['must_change_password']['isActive'] ?? false), fn (Collection $rows): Collection => $rows->where('must_change_password', true))
            ->when(filled($filters['joined_from']['joined_from'] ?? null), fn (Collection $rows): Collection => $rows->where('joined_at', '>=', $filters['joined_from']['joined_from']))
            ->when(filled($filters['has_orders']['value'] ?? null), fn (Collection $rows): Collection => $rows->filter(
                fn (array $row): bool => ($row['orders_count'] > 0) === (bool) $filters['has_orders']['value'],
            ))
            ->when(filled($sortColumn), fn (Collection $rows): Collection => $rows->sortBy(self::SORT_KEYS[$sortColumn] ?? $sortColumn, SORT_NATURAL | SORT_FLAG_CASE, $sortDirection === 'desc'));

        $perPage = $recordsPerPage === 'all' ? max($records->count(), 1) : (int) $recordsPerPage;

        return new LengthAwarePaginator(
            $records->forPage($page, $perPage),
            total: $records->count(),
            perPage: $perPage,
            currentPage: $page,
        );
    }

    /**
     * The record shown in the infolist (template C), with deliberately empty values.
     *
     * @return array<string, mixed>
     */
    public static function shownRecord(): array
    {
        return [
            ...self::all()[2],
            'phone' => null,
            'company_name' => 'AutoGlass Piotr Nowak',
            'company_nip' => '7792345678',
            'company_address' => null,
            'notes' => null,
        ];
    }

    /**
     * @return array{name: string, email: string, account_type: string, role: string, status: string, must_change_password: bool, joined_at: string, updated_at: string, orders_count: int}
     */
    private static function row(string $name, string $email, string $accountType, string $role, string $status, bool $mustChangePassword, string $joinedAt, string $updatedAt, int $ordersCount): array
    {
        return [
            'name' => $name,
            'email' => $email,
            'account_type' => $accountType,
            'role' => $role,
            'status' => $status,
            'must_change_password' => $mustChangePassword,
            'joined_at' => $joinedAt,
            'updated_at' => $updatedAt,
            'orders_count' => $ordersCount,
        ];
    }
}
