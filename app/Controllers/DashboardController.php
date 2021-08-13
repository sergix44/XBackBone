<?php

namespace App\Controllers;

use App\Database\Repositories\MediaRepository;
use App\Database\Repositories\TagRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class DashboardController extends Controller
{
    /**
     * @param  Request  $request
     * @param  Response  $response
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
     * @param  Request  $request
     * @param  Response  $response
     * @param  int|null  $page
     *
     * @return Response
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     *
     * @throws \Twig\Error\LoaderError
     */
    public function home(Request $request, Response $response, int $page = 0): Response
    {
        $page = max(0, --$page);

        switch (param($request, 'sort', 'time')) {
            case 'size':
                $order = MediaRepository::ORDER_SIZE;
                break;
            case 'name':
                $order = MediaRepository::ORDER_NAME;
                break;
            default:
            case 'time':
                $order = MediaRepository::ORDER_TIME;
                break;
        }

        $isAdmin = (bool) $this->session->get('admin', false);

        /** @var MediaRepository $query */
        $query = make(MediaRepository::class, ['isAdmin' => $isAdmin])
            ->orderBy($order, param($request, 'order', 'DESC'))
            ->withUserId($this->session->get('user_id'))
            ->search(param($request, 'search', null))
            ->filterByTag(param($request, 'tag'))
            ->run($page);

        $tags = make(TagRepository::class, [
            'isAdmin' => $isAdmin,
            'userId' => $this->session->get('user_id')
        ])->all();

        return view()->render(
            $response,
            ($this->session->get('gallery_view', $isAdmin)) ? 'dashboard/list.twig' : 'dashboard/grid.twig',
            [
                'medias' => $query->getMedia(),
                'next' => $page < floor($query->getPages()),
                'previous' => $page >= 1,
                'current_page' => ++$page,
                'copy_raw' => $this->session->get('copy_raw', false),
                'tags' => $tags,
            ]
        );
    }

    /**
     * @param  Response  $response
     *
     * @return Response
     */
    public function switchView(Response $response): Response
    {
        $isAdmin = (bool) $this->session->get('admin', false);

        $this->session->set('gallery_view', !$this->session->get('gallery_view', $isAdmin));

        return redirect($response, route('home'));
    }
}
