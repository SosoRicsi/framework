<?php
namespace ApiPHP;

class Auth
{
	public function handle(Http\Request $request, Http\Response $response)
	{
		if ($request->isMethod('GET')) {
			return true;
		}

		$response->setStatusCode(400)
				->setBody("Wong method! [POST] expected, [{$request->getMethod()}] given.")
				->send();
	}
}