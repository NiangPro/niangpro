<?php

namespace App\Controllers\Admin;

use App\Models\Order;
use App\Support\AdminQuery;
use Niang\Core\Controller;
use Niang\Core\Database\DB;
use Niang\Core\Http\Request;
use Niang\Core\Http\Response;

/** Commandes : liste filtrable (statut, recherche), détail, changement de statut. */
class OrderController extends Controller
{
    public function index(Request $request): Response
    {
        $status = (string) $request->input('statut', '');
        $search = trim((string) $request->input('q', ''));
        $where = ['1 = 1'];
        $bindings = [];

        if (in_array($status, Order::STATUSES, true)) {
            $where[] = 'status = ?';
            $bindings[] = $status;
        } else {
            $status = '';
        }

        if ($search !== '') {
            $where[] = "(reference LIKE ? ESCAPE '!' OR name LIKE ? ESCAPE '!' OR email LIKE ? ESCAPE '!')";
            array_push($bindings, ...array_fill(0, 3, AdminQuery::like($search)));
        }

        $counts = ['' => 0];

        foreach (DB::select('SELECT status, COUNT(*) AS n FROM orders GROUP BY status') as $row) {
            $counts[$row['status']] = (int) $row['n'];
            $counts[''] += (int) $row['n'];
        }

        return $this->view('admin/orders/index', [
            'orders' => AdminQuery::paginate('*', 'orders WHERE ' . implode(' AND ', $where), $bindings, 'id DESC', 15, (int) $request->input('page', 1)),
            'status' => $status,
            'search' => $search,
            'counts' => $counts,
        ]);
    }

    public function show(string $id): Response
    {
        $order = Order::find((int) $id) ?? abort(404, 'Commande introuvable.');

        return $this->view('admin/orders/show', ['order' => $order, 'items' => Order::items($order['id'])]);
    }

    public function updateStatus(Request $request, string $id): Response
    {
        $order = Order::find((int) $id) ?? abort(404, 'Commande introuvable.');
        $data = $this->validate($request, ['status' => 'required|in:' . implode(',', Order::STATUSES)]);

        Order::update($order['id'], ['status' => $data['status'], 'updated_at' => date('Y-m-d H:i:s')]);

        return $this->redirect('/admin/commandes/' . $order['id'])
            ->with('success', 'Statut de la commande ' . $order['reference'] . ' : ' . mb_strtolower(Order::statusLabel($data['status'])) . '.');
    }
}
