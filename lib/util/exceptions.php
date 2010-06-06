<?php
/*
 * Copyright (c) 2010 by Justin Otherguy <justin@justinotherguy.org>
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License (either version 2 or
 * version 3) as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
 *
 * For more information on the GPL, please go to:
 * http://www.gnu.org/copyleft/gpl.html
 */

class CustomException extends Exception {
	public function toXml(DOMDocument $doc) {
		$xmlRecord = $doc->createElement('exception');
		$xmlRecord->setAttribute('code', $this->code);

		$xmlRecord->appendChild($doc->createElement('message', $this->message));
		$xmlRecord->appendChild($doc->createElement('line', $this->line));
		$xmlRecord->appendChild($doc->createElement('file', $this->file));

		$xmlRecord->appendChild(backtrace2xml($this->getTrace(), $doc));

		return $xmlRecord;
	}

	public function toHtml() {
		return $this->message . ' in ' . $this->file . ':' . $this->line;
	}
}

class CustomErrorException extends ErrorException {
	
	static public function errorHandler($errno, $errstr, $errfile, $errline ) {
		throw new self($errstr, 0, $errno, $errfile, $errline);
	}
}

?>