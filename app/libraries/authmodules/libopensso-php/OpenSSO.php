<?php
/**
 * OpenSSO integration library for PHP
 *
 * Jorge López Pérez <jorgelp@us.es>
 *
 *  v0.2 , 8/may/2010
 */

require_once('config.php');

class OpenSSO {
	private $cookiename;
	private $token;
	private $err;
	private $attributes;

	function OpenSSO($fetch_cookie_name = FALSE) {
		if ($fetch_cookie_name === TRUE) {
			// Fetch cookie name
			$res = $this->identity_query(OPENSSO_COOKIE_NAME_FETCH, 'POST');
			if (isset($res->error) || $res->code != '200') {
				if (!isset($res->error)) {
					$this->error = 'HTTP result = ' . $res->code;
				} else {
					$this->error = $res->error;
				}

				return;
			}

			$this->cookiename = preg_replace('/^string=/', '', $res->data);
		} else {
			$this->cookiename = OPENSSO_COOKIE_NAME;
		}


		// Retrieve token from GET or cookie (IE bug)
		if (isset($_GET[$this->cookiename]) &&
				(!isset($_COOKIE[$this->cookiename]) ||
				 $_COOKIE[$this->cookiename] != $_GET[$this->cookiename])) {
			$this->token = $_GET[$this->cookiename];
			// Internet Explorer workaround
			setcookie($this->cookiename, $this->token, 0, '/');
		} elseif (isset($_COOKIE[$this->cookiename])) {
			// Incorrect encoding of + to " "
			$this->token = preg_replace('/ /', '+',
					$_COOKIE[$this->cookiename]);
		}
	}

	/**
	 * Check for errors
	 */
	function check_error() {
		if (!empty($this->error)) {
			return $this->error;
		} else {
			return FALSE;
		}
	}

	/**
	 * Forces OpenSSO login
	 *
	 * @param string	Return URL. If not specified, current URL is used
	 */
	function check_and_force_sso($gotourl = '') {
		/*
		 * 1. Look for current token
		 * 2. If not present, redirect user
		 * 3. If present, check for validity
		 * 3.1. If valid session found, return TRUE
		 * 3.2. If not, redirect user
		 */
		if (!$this->check_sso()) {
			if (empty($gotourl)) {
				$gotourl = $this->current_url();
			}

			if (!$this->check_error()) {
				header("Location: " . OPENSSO_LOGIN_URL . '?goto='
						. urlencode($gotourl));
			} 

			return FALSE;
		} else {
			return TRUE;
		}
	}

	/**
	 * Just checks if the user has an opened session to OpenSSO.
	 * 
	 * Fetchs user attributes if a valid session is found
	 */

	function check_sso() {
		if (empty($this->token)) {
			return FALSE;
		}

		// Check for valid session
		$res = $this->identity_query(OPENSSO_IS_TOKEN_VALID, 'GET',
				'tokenid=' . urlencode($this->token));
		if (isset($res->error) || $res->code != '200') {
			if (isset($res->code) && $res->code == '403') {
				// IP with no permission to access OpenSSO web services
				?>
					<div style="background-color: red; padding: 1em; color: #ffffff; font-weight: bold">
					IP sin acceso a OpenSSO
					</div>
				<?php
				exit;
			} elseif ($res->code == '401') {
				// Caso token inválido
			} elseif (!isset($res->error)) {
				$this->error = 'HTTP result = ' . $res->code;
			} else {
				$this->error = $res->error;
			}
			$this->token = '';
			return FALSE;
		} else {
			if (preg_match('/true/', $res->data)) {
				// SSO token is valid
				$this->fetch_attributes();
				return TRUE;
			} else {
				$this->token = '';
				return FALSE;
			}
		}
	}


	/**
	 * Fetchs user attributes
	 */

	function fetch_attributes() {
		if (empty($this->token)) {
			$this->error = 'fetch_attributes(): empty token';
			return FALSE;
		}

		$res = $this->identity_query(OPENSSO_ATTRIBUTES, 'GET',
				'subjectid=' . urlencode($this->token));
		if (isset($res->error) || $res->code != '200') {
			if (!isset($res->error)) {
				$this->error = 'HTTP result = ' . $res->code;
			} else {
				$this->error = $res->error;
			}
			return FALSE;
		} else {
			$attributes = array();

			$lines = preg_split("/\r\n|\n|\r/", $res->data);
			$atr = "";
			$values = array();
			foreach ($lines as $line) {
				$piece = preg_split("/=/", $line);
				if ($piece[0] == "userdetails.attribute.name") {
					// Store attribute
					if (!empty($atr)) {
						$atr = strtolower($atr);
						$this->attributes[$atr] = count($values) == 1 ? 
									$values[0] :
									$values;
						$values = array();
					}
					$atr = $piece[1];
				} else if ($piece[0] == "userdetails.attribute.value") {
					$values[] = $piece[1];
				}
			}

			// Last attribute
			if (!empty($atr)) {
				$this->attributes[$atr] = count($values) == 1 ? 
							$values[0] :
							$values;
				$values = array();
			}
		}
	}

	/**
	 * Connects to an OpenSSO identity service and retrieves answer
	 *
	 * Returns an object with the following attributes:
	 *
	 * ->error:  Errors
	 * ->code: HTTP answer code
	 * ->data:  Data answered from server
	 */

	function identity_query($url, $method = 'GET', $query = '') {
		$result = new stdClass();
		$uri = parse_url($url);

		switch ($uri['scheme']) {
			case 'http':
				$port = isset($uri['port']) ? $uri['port'] : 80;
				$host = $uri['host'] . ($port != 80 ? ':'. $port : '');
				$fp = @fsockopen($uri['host'], $port, $errno, $errstr, 15);
				break;
			case 'https':
				$port = isset($uri['port']) ? $uri['port'] : 443;
				$host = $uri['host'] . ($port != 443 ? ':'. $port : '');
				$fp = @fsockopen('ssl://'. $uri['host'], $port, $errno, $errstr, 20);
				break;
			default:
				$result->error = 'Invalid protocol: '. $uri['scheme'];
				return $result;
		}

		if (!$fp) {
			$result->error = trim($errno .' '. $errstr);
			return $result;
		}
		
		$path = isset($uri['path']) ? $uri['path'] : '/';
		if (!empty($query)) {
			$path .= '?' . $query;
		}

		// Create HTTP request.
		$defaults = array(
				'Host' => "Host: $host",
				'User-Agent' => 'User-Agent: libopensso 0.1',
		);

		$request = $method .' '. $path ." HTTP/1.0\r\n";
		$request .= implode("\r\n", $defaults);
		$request .= "\r\n\r\n";

		fwrite($fp, $request);

		// Fetch response.
		$response = '';
		while (!feof($fp) && $chunk = fread($fp, 1024)) {
			$response .= $chunk;
		}
		fclose($fp);

		// Parse response.
		$tmpdata = '';
		list($split, $tmpdata) = explode("\r\n\r\n", $response, 2);
		$split = preg_split("/\r\n|\n|\r/", $split);

		list($protocol, $code, $text) = explode(' ', trim(array_shift($split)), 3);

		$result->code = $code;
		$result->data = trim($tmpdata);

		return $result;
	}


	/**
	 * Returns an attribute value/values
	 */

	function attribute($atr, $force_array = FALSE) {
		if (empty($atr)) {
			$this->error = 'attribute(): empty attribute name';
			return FALSE;
		} else {
			$atr = strtolower($atr);
			if (isset($this->attributes[$atr])) {
				if ($force_array && !is_array($this->attributes[$atr])) {
					return array($this->attributes[$atr]);
				} else {
					return $this->attributes[$atr];
				}
			} else {
				return $force_array ? array() : '';
			}
		}
	}

	/**
	 * Returns all attributes
	 * 
	 * @param boolean	Force use of arrays even on single valued attributes
	 */
	function all_attributes($force_arrays = FALSE) {
		$atr = array();
		if ($force_arrays === TRUE) {
			foreach ($this->attributes as $a => $v) {
				if (!is_array($v)) {
					$v = array($v);
				}

				$atr[$a] = $v;
			}
		} else {
			$atr = $this->attributes;
		}
		return $atr;
	}


	/**
	 * Logs out user from OpenSSO
	 * 
	 * @param boolean	Use OpenSSO logout page
	 * @param string	Back logout URL
	 */
	function logout($use_logout_page = FALSE, $gotourl = '') {
		// IE bug. If testExplorerBug cookie is not set, it means
		// it didn't store any cookies for *.xx.tld, so
		// unset cookie for current hostname
		if (!isset($_COOKIE['testExplorerBug'])) {
			setcookie($this->cookiename, "", time() - 3600, "/");
		}

		if ($use_logout_page) {
			$gotourl = empty($gotourl) ? $this->current_url() : $gotourl;
			header("Location: " . OPENSSO_LOGOUT_URL . "?goto=" 
					. urlencode($gotourl));
		} else {
			$this->identity_query(OPENSSO_LOGOUT_SERVICE, 'GET', 'subjectid=' .
					urlencode($this->token));
			// Borrado de cookie
			unset($_COOKIE[$this->cookiename]);
			setcookie($this->cookiename, "", time() - 3600, "/",
					OPENSSO_DOMAIN);
		}
	}



	/**
	 * Returns current URL
	 */

	private function current_url() {
		return (isset($_SERVER['HTTPS']) ? 'https' : 'http')
			. '://' . $_SERVER['SERVER_NAME']  . ':'
			. $_SERVER['SERVER_PORT']
			. $_SERVER['REQUEST_URI'];
	}

}

?>
