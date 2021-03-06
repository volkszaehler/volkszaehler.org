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
use Doctrine\DBAL\Logging\SQLLogger;
use SqlFormatter;

/**
 * A SQL logger that logs to the standard output using echo/var_dump.
 *
 * @link   www.doctrine-project.org
 * @since  2.0
 * @author Andreas Goetz <cpuidle@gmx.de>
 */
class ConsoleSQLLogger implements SQLLogger
{
	/**
	 * @var OutputInterface
	 */
	protected $output;

	private $queryStarted;

	public function __construct(OutputInterface $output) {
		$this->output = $output;
	}

	/**
	 * {@inheritdoc}
	 */
	public function startQuery($sql, array $params = null, array $types = null)
	{
		$sql = Debug::getParametrizedQuery($sql, $params);
		if (class_exists(SqlFormatter::class)) {
			$sql = SqlFormatter::format($sql);
		}
		// echo PHP_EOL . $sql . PHP_EOL;
		$this->output->writeln(PHP_EOL . $sql);
		$this->queryStarted = microtime(true);
	}

	/**
	 * {@inheritdoc}
	 */
	public function stopQuery()
	{
		printf("Execution time: %.3f s\n", microtime(true) - $this->queryStarted);
	}
}
