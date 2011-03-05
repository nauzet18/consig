<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
 * Copyright 2011 Jorge López Pérez <jorgelp@us.es>
 *
 *    This file is part of Consigna.
 *
 *    Consigna is free software: you can redistribute it and/or modify it
 *    under the terms of the GNU Affero General Public License as
 *    published by the Free Software Foundation, either version 3 of the
 *    License, or (at your option) any later version.
 *
 *    Consigna is distributed in the hope that it will be useful, but
 *    WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 *    Affero General Public License for more details.
 *
 *    You should have received a copy of the GNU Affero General Public
 *    License along with Consigna.  If not, see
 *    <http://www.gnu.org/licenses/>.
 */

class MY_Config extends CI_Config {

	function MY_Config() {
        parent::CI_Config();
	}

	/**
	 * Site URL
	 *
	 * @access	public
	 * @param	string	the URI string
	 * @return	string
	 */
	function site_url($uri = '')
	{
		if (is_array($uri))
		{
			$uri = implode('/', $uri);
		}

		if ($uri == '')
		{
			return $this->slash_item('base_url').$this->item('index_page');
		}
		else
		{
			$suffix = ($this->item('url_suffix') == FALSE) ? '' : $this->item('url_suffix');
			$inicio =
				$this->slash_item('base_url').$this->slash_item('index_page');
			$final = '';

			if ($this->item('autoversionar_estaticos') === FALSE) {
				$final= $uri; 
			} else {
				$partes = pathinfo($uri);
				if (!isset($partes['extension'])) {
					$final = $uri;
				} else {
					$final = 
						$partes['dirname'] . '/'.
						$partes['filename'] . '+' .
						urlencode(VERSIONCONSIGNA) .
						'.' .  $partes['extension'];
				}
			}

			return $inicio . trim($final, '/') . $suffix;
		}
	}
}
