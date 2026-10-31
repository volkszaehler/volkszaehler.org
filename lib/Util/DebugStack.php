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

/**
 * Collects queries and their execution time for the debug output
 */
class DebugStack implements SQLLogger
{
	/**
	 * @var array<int, array{sql: string, params: ?array, types: ?array, executionMS: float}>
	 */
	public $queries = [];

	private $start;

	private $current = 0;

	public function startQuery($sql, ?array $params = null, ?array $types = null)
	{
		$this->start = microtime(true);
		$this->queries[++$this->current] = ['sql' => $sql, 'params' => $params, 'types' => $types, 'executionMS' => 0];
	}

	public function stopQuery()
	{
		$this->queries[$this->current]['executionMS'] = microtime(true) - $this->start;
	}
}
