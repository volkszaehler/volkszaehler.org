<?php
/**
 * Interpreter functions
 *
 * @author Andreas Goetz <cpuidle@gmx.de>
 * @copyright Copyright (c) 2011-2020, The volkszaehler.org project
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License version 3
 */

namespace Tests;

use Volkszaehler\Router;
use Volkszaehler\Util\EntityFactory;
use Volkszaehler\Interpreter\Interpreter;

trait InterpreterTrait {

	protected $em;
	protected $ef;
	protected $uuid;

	function createChannel($title, $type, $other = []) {
		$url = '/channel.json';
		$params = array(
			'operation' => 'add',
			'title' => $title,
			'type' => $type
		);
		$params = array_merge($params, $other);

		$this->getJson($url, $params);

		return($this->uuid = (isset($this->json->entity->uuid)) ? $this->json->entity->uuid : null);
	}

	function deleteChannel($uuid) {
		$entity = $this->ef->getByUuid($uuid);
		$this->em->remove($entity);
		$this->em->flush();
	}

	function createInterpreter($uuid, $from, $to, $tuples, $groupBy, $options= array()) {
		if (!isset($this->em)) {
			$this->em = Router::createEntityManager();
			$this->ef = EntityFactory::getInstance($this->em);
		}
		$entity = $this->ef->getByUuid($uuid);
		$class = $entity->getDefinition()->getInterpreter();
		$interpreter = new $class($entity, $this->em, $from, $to, $tuples, $groupBy, $options);
		return $interpreter;
	}

	function getInterpreterResult(Interpreter $interpreter) {
		$tuples = array();
		foreach ($interpreter as $tuple) {
			$tuple[0] = (int)$tuple[0];
			$tuples[] = $tuple;
		}
		return $tuples;
	}
}

?>
