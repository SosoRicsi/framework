<?php
namespace ApiPHP;

class Auth
{
	public function handle(Http\Request $request, Http\Response $response)
	{
		$request->isMethod('POST', function () use ($response) {
			$response->setStatusCode(202)
					->setBody([
						'username' => "Ricsi"
					])
					->send();
		}, function () use ($response) {
			$response->setStatusCode(400)
					->send();
		});
	}
}