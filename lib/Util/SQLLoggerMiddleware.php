<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Volkszaehler\Util;

use Symfony\Component\Console\Output\OutputInterface;
namespace Volkszaehler\Util;

use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Middleware;
use Doctrine\DBAL\Driver\Middleware\AbstractConnectionMiddleware;
use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;
use Doctrine\DBAL\Driver\Middleware\AbstractStatementMiddleware;
use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\ParameterType;

/**
 * Passes queries to the logger attached at runtime, DBAL 4 only takes middlewares when connecting
 */
class SQLLoggerMiddleware implements Middleware
{
	/**
	 * @var SQLLogger|null
	 */
	public static $logger;

	public static function log(string $sql, ?array $params, callable $query) {
		if (!self::$logger) {
			return $query();
		}
		self::$logger->startQuery($sql, $params);
		try {
			return $query();
		}
		finally {
			self::$logger->stopQuery();
		}
	}

	public function wrap(Driver $driver): Driver {
		return new class($driver) extends AbstractDriverMiddleware {
			public function connect(array $params): Driver\Connection {
				return new class(parent::connect($params)) extends AbstractConnectionMiddleware {
					public function prepare(string $sql): Statement {
						return new class(parent::prepare($sql), $sql) extends AbstractStatementMiddleware {
							private $params = [];

							public function __construct(Statement $statement, private string $sql) {
								parent::__construct($statement);
							}

							public function bindValue(int|string $param, mixed $value, ParameterType $type): void {
								$this->params[$param] = $value;
								parent::bindValue($param, $value, $type);
							}

							public function execute(): Result {
								return SQLLoggerMiddleware::log($this->sql, $this->params, fn() => parent::execute());
							}
						};
					}

					public function query(string $sql): Result {
						return SQLLoggerMiddleware::log($sql, null, fn() => parent::query($sql));
					}

					public function exec(string $sql): int|string {
						return SQLLoggerMiddleware::log($sql, null, fn() => parent::exec($sql));
					}
				};
			}
		};
	}
}
