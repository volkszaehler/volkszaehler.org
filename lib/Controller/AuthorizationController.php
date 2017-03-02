<?php
/**
 * @copyright Copyright (c) 2011, The volkszaehler.org project
 * @package default
 * @license http://www.opensource.org/licenses/gpl-license.php GNU Public License
 */
/*
 * This file is part of volkzaehler.org
 *
 * volkzaehler.org is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * volkzaehler.org is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with volkszaehler.org. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Volkszaehler\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManager;
use Volkszaehler\Util;
use Volkszaehler\View\View;
use Firebase\JWT\JWT;
use Firebase\JWT\ExpiredException;

/**
 * Controller for user authorization
 *
 * See http://stackoverflow.com/questions/3297048/403-forbidden-vs-401-unauthorized-http-responses
 * and https://tools.ietf.org/rfc/rfc7231.txt and https://tools.ietf.org/rfc/rfc7235.txt
 * for an in-depth discussions on what HTTP reponse headers to use. In short:
 *
 *		401 UNAUTHORIZED	Indicates that the request has not been applied because it lacks
 * 							valid authentication credentials for the target resource.
 *							Response MUST send a WWW-Authenticate header field.
 *
 *		403	FORBIDDEN		Indicates that the server understood the request but refuses to authorize it.
 *							If authentication credentials were provided in the request, the
 *							server considers them insufficient to grant access.
 *
 * @author Andreas Goetz <cpuidle@gmx.de>
 * @package default
 */
class AuthorizationController extends Controller {

	const TOKEN_LIFETIME = 24 * 3600;

	/**
	 * Authorize request via header
	 */
	public static function authorize(Request $request, View $view) {
		if (!($key = Util\Configuration::read('authorization.secretkey'))) {
			$view->getResponse()->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
			throw new \Exception('Missing authorization.secretkey');
		}

		try {
			if (!($header = $request->headers->get('Authorization')) || (0 !== strpos($header, 'Bearer '))) {
				throw new \Exception('Missing authorization token');
			}

			$jwt = substr($header, strlen('Bearer '));
			if ($token = JWT::decode($jwt, $key, array('HS256'))) {
				// Check if this token has expired due to changed config
				if (isset($token->iat) && ($token->iat + Util\Configuration::read('authorization.valid', self::TOKEN_LIFETIME)) < time()) {
					throw new ExpiredException('Expired token');
				}
			}
		}
		catch (\Exception $e) {
			$view->getResponse()->headers->set('WWW-Authenticate', 'Bearer');
			$view->getResponse()->setStatusCode(Response::HTTP_UNAUTHORIZED);
			throw($e);
		}
	}

	/**
	 * Run operation
	 *
	 * @param string $operation runs the operation if class method is available
	 */
	public function add() {
		try {
			$json = Util\JSON::decode($this->request->getContent());
		}
		catch (\Exception $e) {
			$this->view->getResponse()->setStatusCode(Response::HTTP_FORBIDDEN);
			throw new \Exception('Invalid token request');
		}
/*
		// firebase token auth
		if (isset($json->authtype) && $json->authtype == 'firebase' && isset($json->authtoken)) {
			$authtoken = trim($json->authtoken);

			$googlePublicKeys = json_decode(file_get_contents(VZ_DIR . '/etc/firebase_public.json'), true);
			$token = JWT::decode($authtoken, $googlePublicKeys, ['RS256']);

			$auth = Util\Configuration::read('users.firebase');

			if (in_array($token->email, $auth)) {
				$jwt = $this->issueToken($token->email);
				return(['authtoken' => $jwt]);
			}
		}
		else
*/
		// username/password
		if (isset($json->username) && isset($json->password)) {
			$username = strtolower(trim($json->username));
			$password = trim($json->password);

			$auth = Util\Configuration::read('users.plain');

			if (isset($auth[$username]) && $password === $auth[$username]) {
				$jwt = $this->issueToken($json->username);
				return(['authtoken' => $jwt]);
			}
		}

		$this->view->getResponse()->setStatusCode(Response::HTTP_FORBIDDEN);
		throw new \Exception('Invalid user credentials');
	}

	/**
	 * Issue bearer auth token identifying user by name
	 */
	protected function issueToken($username) {
		if (!($key = Util\Configuration::read('authorization.secretkey'))) {
			$this->view->getResponse()->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
			throw new \Exception('Missing authorization.secretkey');
		}

		$token = array(
			"iat" => time(),
			"exp" => time() + Util\Configuration::read('authorization.valid', self::TOKEN_LIFETIME),
			"sub" => $username
		);

		return JWT::encode($token, $key);
	}
}

?>
