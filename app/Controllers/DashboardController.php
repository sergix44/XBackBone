<?php

namespace App\Controllers;

use App\Database\Queries\MediaQuery;
use Slim\Http\Request;
use Slim\Http\Response;

class DashboardController extends Controller
{

	/**
	 * @param Request $request
	 * @param Response $response
	 * @return Response
	 */
	public function redirects(Request $request, Response $response): Response
	{
		if ($request->getParam('afterInstall') !== null && !is_dir(BASE_DIR . 'install')) {
			$this->session->alert(lang('installed'), 'success');
		}

		return redirect($response, 'home');
	}

	/**
	 * @param Request $request
	 * @param Response $response
	 * @param $args
	 * @return Response
	 */
	public function home(Request $request, Response $response, $args): Response
	{
		$page = isset($args['page']) ? (int)$args['page'] : 0;
		$page = max(0, --$page);

		$query = new MediaQuery($this->database, $this->session->get('admin', false), $this->storage);

		switch ($request->getParam('sort', 'time')) {
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

		$query->orderBy($order, $request->getParam('order', 'DESC'))
			->withUserId($this->session->get('user_id'))
			->search($request->getParam('search', null))
			->run($page);

		return $this->view->render(
			$response,
			($this->session->get('admin', false) && $this->session->get('gallery_view', true)) ? 'dashboard/admin.twig' : 'dashboard/home.twig',
			[
				'medias' => $query->getMedia(),
				'next' => $page < floor($query->getPages()),
				'previous' => $page >= 1,
				'current_page' => ++$page,
			]
		);
	}

	/**
	 * @param Request $request
	 * @param Response $response
	 * @param $args
	 * @return Response
	 */
	public function switchView(Request $request, Response $response, $args): Response
	{
		$this->session->set('gallery_view', !$this->session->get('gallery_view', true));
		return redirect($response, 'home');
	}
}