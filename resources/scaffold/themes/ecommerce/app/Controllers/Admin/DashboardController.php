<?php

namespace App\Controllers\Admin;

use App\Models\Order;
use App\Models\Product;
use App\Support\AdminFormat;
use App\Support\Money;
use Niang\Core\Controller;
use Niang\Core\Database\DB;
use Niang\Core\Http\Response;

/** Vue d'ensemble de la boutique : chiffre d'affaires, commandes, clients, stock. */
class DashboardController extends Controller
{
    /** Nombre de jours affichés sur la courbe des ventes. */
    private const CHART_DAYS = 14;

    public function index(): Response
    {
        $since = date('Y-m-d', strtotime('-29 days'));
        $previous = date('Y-m-d', strtotime('-59 days'));

        $revenue = $this->revenueBetween($since, null);
        $previousRevenue = $this->revenueBetween($previous, $since);
        $paidOrders = (int) (DB::selectOne("SELECT COUNT(*) AS n FROM orders WHERE status != 'cancelled'")['n'] ?? 0);
        $totalRevenue = (int) (DB::selectOne("SELECT COALESCE(SUM(total_cents), 0) AS s FROM orders WHERE status != 'cancelled'")['s'] ?? 0);

        return $this->view('admin/dashboard', [
            'stats' => [
                'revenue' => $revenue,
                'revenueTrend' => $previousRevenue > 0 ? ($revenue - $previousRevenue) * 100 / $previousRevenue : null,
                'orders' => Order::query()->where('created_at', '>=', $since)->count(),
                'pending' => Order::query()->where('status', 'pending')->count(),
                'average' => $paidOrders > 0 ? intdiv($totalRevenue, $paidOrders) : 0,
                'customers' => (int) (DB::selectOne("SELECT COUNT(*) AS n FROM users WHERE role != 'admin'")['n'] ?? 0),
                'products' => Product::query()->count(),
            ],
            'chart' => $this->dailyRevenue(),
            'statuses' => $this->statusBreakdown(),
            'latest' => Order::query()->orderBy('id', 'desc')->limit(6)->get(),
            'topProducts' => DB::select(
                'SELECT order_items.name, SUM(order_items.quantity) AS sold, SUM(order_items.quantity * order_items.unit_price_cents) AS revenue '
                . "FROM order_items INNER JOIN orders ON orders.id = order_items.order_id WHERE orders.status != 'cancelled' "
                . 'GROUP BY order_items.name ORDER BY sold DESC, revenue DESC LIMIT 5'
            ),
            'lowStock' => Product::query()->where('stock', '<=', Product::LOW_STOCK)->orderBy('stock')->limit(5)->get(),
        ]);
    }

    /** Chiffre d'affaires (hors commandes annulées) entre deux dates, fin exclue. */
    private function revenueBetween(string $from, ?string $to): int
    {
        $sql = "SELECT COALESCE(SUM(total_cents), 0) AS s FROM orders WHERE status != 'cancelled' AND created_at >= ?";
        $bindings = [$from];

        if ($to !== null) {
            $sql .= ' AND created_at < ?';
            $bindings[] = $to;
        }

        return (int) (DB::selectOne($sql, $bindings)['s'] ?? 0);
    }

    /**
     * Ventes jour par jour, regroupées en PHP : même code pour SQLite et MySQL, dont les fonctions
     * de date diffèrent.
     *
     * @return list<array{label: string, value: int, display: string}>
     */
    private function dailyRevenue(): array
    {
        $days = [];

        for ($i = self::CHART_DAYS - 1; $i >= 0; $i--) {
            $days[date('Y-m-d', strtotime("-$i days"))] = 0;
        }

        $rows = DB::select(
            "SELECT created_at, total_cents FROM orders WHERE status != 'cancelled' AND created_at >= ?",
            [array_key_first($days)]
        );

        foreach ($rows as $row) {
            $day = substr((string) $row['created_at'], 0, 10);

            if (array_key_exists($day, $days)) {
                $days[$day] += (int) $row['total_cents'];
            }
        }

        $points = [];

        foreach ($days as $day => $cents) {
            $points[] = ['label' => AdminFormat::dayMonth($day), 'value' => $cents, 'display' => Money::format($cents)];
        }

        return $points;
    }

    /** @return list<array{label: string, value: int, display: string, href: string}> */
    private function statusBreakdown(): array
    {
        $counts = array_fill_keys(Order::STATUSES, 0);

        foreach (DB::select('SELECT status, COUNT(*) AS n FROM orders GROUP BY status') as $row) {
            $counts[$row['status']] = (int) $row['n'];
        }

        $rows = [];

        foreach ($counts as $status => $count) {
            $rows[] = ['label' => Order::statusLabel($status), 'value' => $count, 'display' => (string) $count, 'href' => '/admin/commandes?statut=' . $status];
        }

        return $rows;
    }
}
