<?php

namespace App\Controllers;

use App\Database\Queries\MediaQuery;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class DashboardController extends Controller
{
    /**
     * @param Request  $request
     * @param Response $response
     *
     * @return Response
     */
    public function redirects(Request $request, Response $response): Response
    {
        if (param($request, 'afterInstall') !== null && !is_dir(BASE_DIR.'install')) {
            $this->session->alert(lang('installed'), 'success');
        }

        return redirect($response, route('home'));
    }

    /**
     * @param Request  $request
     * @param Response $response
     * @param int|null $page
     *
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     *
     * @return Response
     */
    public function home(Request $request, Response $response, int $page = 0): Response
    {
        $page = max(0, --$page);

        $query = new MediaQuery($this->database, $this->session->get('admin', false), $this->storage);

        switch (param($request, 'sort', 'time')) {
            case 'size':
                $order = MediaQuery::ORDER_SIZE;
                break;
            case 'name':
                $order = MediaQuery::ORDER_NAME;
                break;
            default:
            case 'time':
                $order = MediaQuery::ORDER_TIME;
                break;
        }

        $query->orderBy($order, param($request, 'order', 'DESC'))
            ->withUserId($this->session->get('user_id'))
            ->search(param($request, 'search', null))
            ->run($page);

        $copyUrl = $this->database->query('SELECT `value` FROM `settings` WHERE `key` = \'copy_url_behavior\'')->fetch()->value ?? 'off';

        return view()->render(
            $response,
            ($this->session->get('admin', false) && $this->session->get('gallery_view', true)) ? 'dashboard/list.twig' : 'dashboard/grid.twig',
            [
                'medias'       => $query->getMedia(),
                'next'         => $page < floor($query->getPages()),
                'previous'     => $page >= 1,
                'current_page' => ++$page,
                'copy_url_behavior' => $copyUrl,
            ]
        );
    }

    /**
     * @param Response $response
     *
     * @return Response
     */
    public function switchView(Response $response): Response
    {
        $this->session->set('gallery_view', !$this->session->get('gallery_view', true));

        return redirect($response, route('home'));
    }
}
