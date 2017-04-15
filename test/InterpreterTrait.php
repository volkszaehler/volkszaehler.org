<?php
/**
 * Interpreter functions
 *
 * @package Test
 * @author Andreas Götz <cpuidle@gmx.de>
 */

namespace Tests;

use Volkszaehler\Router;
use Volkszaehler\Controller\EntityController;
use Volkszaehler\Interpreter\Interpreter;

trait InterpreterTrait {

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

	function createInterpreter($uuid, $from, $to, $tuples, $groupBy, $options= array()) {
		$em = Router::createEntityManager();
		$entity = EntityController::factory($em, $uuid);
		$class = $entity->getDefinition()->getInterpreter();
		$interpreter = new $class($entity, $em, $from, $to, $tuples, $groupBy, $options);
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
