<?php
/**
 * @copyright Copyright (c) 2016, The volkszaehler.org project
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

namespace Volkszaehler\Exception;

use Symfony\Component\HttpFoundation\Response;

/**
 * ConfigurationException
 *
 * @author Andreas Götz <cpuidle@gmx.de>
 */
class ConfigurationException extends \Exception implements HttpExceptionInterface {

	/**
	 * Get suggested HTTP status code
	 */
	public function getStatusCode() {
		return Response::HTTP_INTERNAL_SERVER_ERROR;
	}
}
