<?php
/**
 * JWT token claims helper
 *
 * @author Andreas Goetz <cpuidle@gmx.de>
 * @copyright Copyright (c) 2011-2018, The volkszaehler.org project
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License version 3
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

namespace Volkszaehler\Util;

use Volkszaehler\Router;

use Firebase\JWT\JWT;

class TokenHelper {

	const TOKEN_CIPHER = 'HS256';
	const TOKEN_LIFETIME = 24 * 3600;

	const TOKEN_CONTEXT = 'vz:ctx';
	const TOKEN_OPERATION = 'vz:ops';

	/**
	 * Get user-specific claims from config
	 *
	 * @return array claims
	 */
	public function getUserConstraints($username) {
		$claims = [];
		$constraints = Configuration::read('users.constraints.' . $username, []);

		if (null !== ($context = @$constraints['context'])) {
			if (is_string($context)) {
				$context = explode(',', $context);
			}
			if (count($context)) {
				$claims[self::TOKEN_CONTEXT] = join(',', $context);
			}
		}

		if (null !== ($operations = @$constraints['operation'])) {
			if (is_string($operations)) {
				$operations = explode(',', $operations);
			}
			$operations = $this->mapOperationsToHttpMethods($operations);
			if (count($operations)) {
				$claims[self::TOKEN_OPERATION] = join(',', $operations);
			}
		}

		return $claims;
	}

	/**
	 * Helper function to convert operations to HTTP methods
	 *
	 * @param array $operations Allowed operations or HTTP methods
	 * @return array Allowed HTTP methods
	 */
	public function mapOperationsToHttpMethods(array $operations) {
		$constraints = [];
		$flipped = array_flip(Router::$operationMapping);

		foreach ($operations as $op) {
			if (in_array(strtoupper($op), array_keys(Router::$operationMapping))) {
				$constraints[] = strtoupper($op);
			}
			elseif (in_array(strtolower($op), array_keys($flipped))) {
				$constraints[] = $flipped[strtolower($op)];
			}
			else {
				throw new \Exception('Invalid operation constraint: ' . $op);
			}
		}

		return array_unique($constraints);
	}

	/**
	 * Issue bearer auth token identifying user by name
	 */
	public function issueToken($username, $claims = []) {
		if (!($key = Configuration::read('authorization.secretkey'))) {
			$this->view->getResponse()->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
			throw new \Exception('Missing authorization.secretkey');
		}

		$token = array(
			'sub' => $username,
			'iat' => time(),
			'exp' => time() + Configuration::read('authorization.valid', self::TOKEN_LIFETIME),
		);

		// additional claims
		$token = array_merge($token, $claims);

		return JWT::encode($token, $key);
	}
}

?>
