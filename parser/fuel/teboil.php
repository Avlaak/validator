<?php
require_once $_SERVER["DOCUMENT_ROOT"].'/common/Validator.class.php';

class teboil extends Validator
{
	protected $domain = 'https://azs.teboil.ru/map/ajax/map.php';
	static $urls = [
		'RU' => ''
	];

	/* Поля объекта */
	protected $fields = [
		'amenity'         => 'fuel',
		'name'            => 'Teboil',
		'name:ru'         => '',
		'brand'           => 'Teboil',
		'operator'        => '',
        'owner'           => 'ООО "Тебойл рус"',
		'contact:website' => 'https://azs.teboil.ru',
		'contact:phone'   => '',
		'ref'             => '',
		'opening_hours'   => '',
		'fuel:octane_98'  => '',
		'fuel:octane_95'  => '',
		'fuel:octane_92'  => '',
		'fuel:octane_80'  => '',
		'fuel:diesel'     => '',
		'fuel:lpg'        => '',
		'fuel:cng'        => '',
		'lat'             => '',
		'lon'             => '',
		'_addr'           => '',
	];

	/* Фильтр для поиска объектов в OSM */
	protected $filter = [
		'[amenity=fuel][name~"Teboil",i]'
	];

	/* Обновление данных по региону */
	public function update()
	{
			$url = $this->domain;
			$query = "cityId[]";
			$page = $this->get_web_page($url, $query);

			if (is_null($page)) {
					return;
			}

			$this->parse($page);
	}

	/* Парсер страницы */
	protected function parse($st)
	{
		$st = json_decode($st, true);
		if (is_null($st)) {
			return;
		}
		foreach ($st['data'][0]['shops'] as $obj) {

			$obj['lat'] = $obj['coordinates'][0];
			$obj['lon'] = $obj['coordinates'][1];
			$obj['_addr'] = $obj['adr'];
			$obj['contact:phone'] = $this->phone($obj['telephone']);
			$obj['ref'] = $obj['externalCode'];

			/* Виды топлива
			$fuels = [
				'92'       => 'fuel:octane_92',
				'95'       => 'fuel:octane_95',
				'95plus'   => 'fuel:octane_95',
				'98'       => 'fuel:octane_98',
				'98plus'   => 'fuel:octane_98',
				'100'      => 'fuel:octane_100',
				'100plus'  => 'fuel:octane_100',
				'lpg'      => 'fuel:lpg',
				'DT'       => 'fuel:diesel',
				'DTplus'   => 'fuel:diesel'
			]; */
			foreach ($obj['fuel'] as $fuel) {
				switch ($fuel['fuelId']) {
					case '92':
						$obj['fuel:octane_92'] = 'yes';
						break;
					case '95': case '95plus':
						$obj['fuel:octane_95'] = 'yes';
						break;
					case '98': case '98plus':
						$obj['fuel:octane_98'] = 'yes';
						break;
					case '100': case '100plus':
						$obj['fuel:octane_100'] = 'yes';
						break;
					case 'lpg':
						$obj['fuel:lpg'] = 'yes';
						break;
					case 'DT': case 'DTplus':
						$obj['fuel:diesel'] = 'yes';
						break;
					default:
						$fuel_id=$fuel['fuelId'];
						$this->log("Parse error! (teboil: Неизвестный вид топлива: '$fuel_id').");
						break;
				}
			}

			$this->addObject($this->makeObject($obj));
		}
	}
}
