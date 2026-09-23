<?php

namespace App\Controllers\Admin;

use App\Support\AdminQuery;
use Niang\Core\Controller;
use Niang\Core\Http\Request;
use Niang\Core\Http\Response;

/** Clients : comptes inscrits avec leur nombre de commandes et leur total dépensé. */
class CustomerController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->input('q', ''));
        $where = "users.role != 'admin'";
        $bindings = [];

        if ($search !== '') {
            $where .= " AND (users.name LIKE ? ESCAPE '!' OR users.email LIKE ? ESCAPE '!')";
            $bindings = [AdminQuery::like($search), AdminQuery::like($search)];
        }

        $select = 'users.id, users.name, users.email, users.created_at, '
            . '(SELECT COUNT(*) FROM orders WHERE orders.user_id = users.id) AS orders_count, '
            . "(SELECT COALESCE(SUM(total_cents), 0) FROM orders WHERE orders.user_id = users.id AND orders.status != 'cancelled') AS spent_cents, "
            . '(SELECT MAX(created_at) FROM orders WHERE orders.user_id = users.id) AS last_order_at';

        return $this->view('admin/customers/index', [
            'customers' => AdminQuery::paginate($select, "users WHERE $where", $bindings, 'users.id DESC', 15, (int) $request->input('page', 1)),
            'search' => $search,
        ]);
    }
}
