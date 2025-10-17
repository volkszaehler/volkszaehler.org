<?php
/**
 * @copyright Copyright (c) 2025, The volkszaehler.org project
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

namespace Volkszaehler\View;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * HTTP OPTIONS request response view
 */
class Options extends View {

	public function __construct(Request $request) {
		parent::__construct($request);
		$this->response = new Response(
			null,
			Response::HTTP_NO_CONTENT
		);
	}

	protected function render() {
		throw new \LogicException('Not needed here');
	}

	/**
	 * Render response and send it to the client
	 */
	public function send(): Response {
		return $this->response;
	}

	/**
	 * Add headers to response
	 */
	public function add($headers) {
		foreach ($headers as $key => $value) {
			$this->response->headers->set($key, $value);
		}
	}
}
