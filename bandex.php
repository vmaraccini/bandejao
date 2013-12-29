<?php

require_once("Encoding.php");

class Bandex {

	var $days = array(
		array('segunda', 'segunda', 'segunda-feira'),
		array('terca', 'ter', 'terça-feira'),
		array('quarta', 'quarta', 'quarta-feira'),
		array('quinta', 'quinta', 'quinta-feira'),
		array('sexta', 'sexta', 'sexta'),
		array('sabado', 'bado', 'sábado'),
		array('domingo', 'domingo', 'domingo')
	);

	var $meals = array(
		array('almoco', 'almo', 'almoço'),
		array('jantar', 'j', 'jantar')
	);

	var $restaurants = array(
		array('central', 'central', 'cardapio.html'),
		array('fisica', 'física', 'cardapiofisica.html'),
		array('prefeitura', 'prefeitura', 'cardcocesp.html'),
		array('quimica', 'química', 'cardapioquimica.html'),
		array('clube', 'clube da universidade', 'carddoc.html')
	);

	var $start_date = NULL;

	const MENU_BASE_URL = 'http://www.usp.br/coseas/';
	const BALANCE_AUTH_URL = 'http://uspdigital.usp.br/rucard/autenticar';
	const BALANCE_EXTRACT_URL = 'http://uspdigital.usp.br/rucard/extratoListar?codmnu=12';
	const TIME_FORMAT = 'd-m-Y';
	const IMPLODE_SUBSTR = '<br>';

	public function Bandex() {
		date_default_timezone_set('America/Sao_Paulo');
	}

	public function get($ids, $options = array()) {

		$menu = array();

		if (!is_array($ids)) {
			if (stripos($ids, ',') !== FALSE)
				$ids = explode(',', $ids);
			else
				$ids = array($ids);
		}

		$options = $this->sanitize($options);

		foreach ($ids as $id) {

			if (!is_numeric($id)) {
				foreach ($this->restaurants as $rId => $r)
					if ($r[0] == trim($id)) {
						$id = $rId;
						break;
					}
			}

			$menu[$this->restaurants[$id][0]] = $this->prettify(
				$this->parse($id),
				$options
			);

		}
			   		
		return $menu;

	}

	private function parse($id) {

		$text = $this->curl(bandex::MENU_BASE_URL . $this->restaurants[$id][2]);

		preg_match(
			'/semana[^\d]+([\d\/]*)[^\d]*([\d\/]*)/i', 
			$text, 
			$period
		);		

		unset($period[0]);

		for ($i = 1; $i <= count($period); $i++) {

			switch (substr_count($period[$i], '/')) {
				case 1: 
					$period[$i] .= date('\/Y');
					break;
				case 0:
					$period[$i] .= date('\/m\/Y');
					break;
			}				

			$period[$i] = date(bandex::TIME_FORMAT, strtotime(str_replace('/', '-', $period[$i])));

		}

		$this->start_date = $period[1];

		preg_match_all(
			'/<td[^>]*>(.*?)<\/td>/mis', 
			$text,
			$td
		);

		$menu = array();

		foreach ($td[1] as $t) {

			preg_match_all(
				'/<font[^>]*>(.*?)<\/font>/mis', 
				strip_tags(
					str_replace(array('span', 'div'), 'font', $t),
					'<font><br><div>'
				),
				$m
			);

			foreach ($this->days as $i => $d)
				if (isset($m[1][0]) && stripos($m[1][0], $d[1]) !== FALSE)
					$menu[$i][] = $m[1];

		}

		return $menu;

	}

	private function prettify($menu, $options) {
	
		$pretty = array();

		foreach ($menu as $dayId => $day) {
		
			if (!in_array($dayId, $options['days']))
				continue;

			foreach ($day as $mealId => $meal) {
					
				if (!in_array($mealId, $options['meals']))
					continue;

				$elems = array();

				foreach ($meal as $elId => $elem)
					if ($elId > 0)
						$elems = array_merge(
							$elems, 
							explode('<br>', nl2br($elem, FALSE))
						);

				foreach ($elems as $elId => $elem) {

					if (stripos($elem, '<font') !== FALSE)
						$elem = preg_replace("/<font.*?>/i", "$1", $elem);

					if (strlen($elem) > 3)
						$elems[$elId] = trim($elem);

				}

				$elems = array_filter($elems);

				$dId = ($options['time_format'] != 'numeric') ?
					date($options['time_format'], strtotime($this->start_date) + 24*60*60*$dayId) :
					$dayId;

				$mId = ($options['meal_format'] == 'name') ?
					$this->meals[$mealId][0] :
					$mealId;

				$pretty[$dId][$mId] = ($options['implode'] == TRUE) ?
					implode(bandex::IMPLODE_SUBSTR, $elems) :
					$elems;

			}

		}

		foreach ($pretty as $day)
			foreach ($day as $meal)
				if (is_array($meal))
					array_filter($meal);
					
		return $pretty;

	}

	public function balance($nusp, $pass) {

		$filename = sha1(date('u') . $nusp . $pass) . '.txt';

		$text = $this->curl(bandex::BALANCE_AUTH_URL . '?' .
			http_build_query(array('codpes' => $nusp, 'senusu' => $pass)),
			$filename
		);

		if (stripos($text, 'extrato') === FALSE)
			return FALSE;

		$text = $this->curl(bandex::BALANCE_EXTRACT_URL, $filename);

		preg_match(
			'/atual[^<]*<[^>]*>[\s]*<[^>]*>([\d]*)/mis',
			$text,
			$balance
		);

		return $balance[1];

	}

	private function sanitize($options) {

		$default = array(
			'time_format' => bandex::TIME_FORMAT,
			'meal_format' => 'name',
			'implode' => FALSE,
			'days' => range(0, 6),
			'meals' => range(0, 1)
		);

		foreach ($default as $key => $value)
			if (!isset($options[$key]))
				$options[$key] = $value;

		return ($options['days'] < 0 || $options['meals'] < 0) ?
			$this->guess_time($options) :
			$options;

	}

	private function guess_time($options, $offset = 0) {

		$offset = 24*60*60;
		$time = time() + $offset;

		$hour = date('H', $time);
		$weekday = date('N', $time) - 1;

		$meal = ($hour >= 15 && $hour < 20) ? 1 : 0;

		if ($hour >= 20)
			$weekday = ($weekday + 1)%7;

		if ($meal == 1 && $weekday >= 5) {
			$meal = 0;
			$weekday = ($weekday + 1)%7;
		}

		if ($options['days'] < 0)
			$options['days'] = array($weekday);

		if ($options['meals'] < 0)
			$options['meals'] = array($meal);

		return $options;

	}

	private function curl($url, $cookie = '', $fields = array()) {

		$curl = curl_init(); 

		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_FAILONERROR, TRUE); 
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE); 
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE); 
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE); 
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);   

		if (!empty($cookie)) {
			curl_setopt($curl, CURLOPT_COOKIEJAR, $cookie); 
			curl_setopt($curl, CURLOPT_COOKIEFILE, $cookie);
		} 

		if (!empty($fields)) {
			curl_setopt($curl, CURLOPT_POST, TRUE);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $fields);
		}

		$result = curl_exec($curl);
		curl_close($curl);

		return $this->convert_encoding($result);
		
	}   	

	private function convert_encoding($content) { 

	    if (!mb_check_encoding($content, 'UTF-8') || !($content === mb_convert_encoding(mb_convert_encoding($content, 'UTF-32', 'UTF-8' ), 'UTF-8', 'UTF-32'))) { 
	        $content = mb_convert_encoding($content, 'UTF-8'); 
	    }
	    
	    $content = Encoding::toUTF8($content);

	    return $content; 

	} 

}
