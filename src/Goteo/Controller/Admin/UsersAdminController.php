<?php
/*
 * This file is part of the Goteo Package.
 *
 * (c) Platoniq y Fundación Goteo <fundacion@goteo.org>
 *
 * For the full copyright and license information, please view the README.md
 * and LICENSE files that was distributed with this source code.
 */

namespace Goteo\Controller\Admin;

use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;
use Goteo\Library\Text;
use Goteo\Model\User;

class UsersAdminController extends AbstractAdminController {
    protected static $icon = '<i class="fa fa-2x fa-users"></i>';

    public static function getRoutes() {
        return [
            new Route(
                '/',
                ['_controller' => __CLASS__ . "::listAction"]
            ),
            new Route(
                '/stats',
                ['_controller' => __CLASS__ . "::statsAction"]
            )
        ];
    }

    public static function getSidebar() {
        return [
            '/users' => Text::get('admin-list'),
            '/users/stats' => Text::get('admin-stats')
        ];
    }


    public function listAction(Request $request) {
        $filters = [];
        $limit = 25;
        $page = $request->query->get('pag') ?: 0;
        $users = User::getList($filters, [], $page * $limit, $limit);
        $total = User::getList($filters, [], 0, 0, true);
        return $this->viewResponse('admin/generic_list', [
            'list' => $users,
            'link_prefix' => '/users/edit/',
            'total' => $total,
            'limit' => $limit
        ]);
    }

    public function statsAction(Request $request) {
        return $this->viewResponse('admin/index', []);
    }


}
