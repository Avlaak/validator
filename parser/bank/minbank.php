<?php
require_once $_SERVER["DOCUMENT_ROOT"].'/common/Validator.class.php';
require_once $_SERVER["DOCUMENT_ROOT"].'/common/regions.php';

class minbank extends Validator
{
	protected $domain = 'https://www.minbank.ru/ajax/isic_address.php';

	static $urls = [
		'RU-MOW' => '20327',
		'RU-SPE' => '20814',
		'RU-ARK' => '20905',
		'RU-AST' => '21932',
		'RU-BEL' => '21185',
		'RU-BRY' => '22218',
		'RU-VLA' => '21469',
		'RU-VGG' => '21237',
		'RU-VOR' => '21003',
		'RU-IVA' => '22323',
		'RU-KB'  => '22685',
		'RU-KLU' => '22367',
		'RU-KC'  => '21901',
		'RU-KOS' => '21431',
		'RU-KDA' => '22265',
		'RU-LEN' => '20825',
		'RU-LIP' => '22412',
		'RU-MOS' => '27635',
		'RU-NEN' => '20994',
		'RU-NIZ' => '21660',
		'RU-ORL' => '21319',
		'RU-AD'  => '22274',
		'RU-KR'  => '20897',
		'RU-SE'  => '21709',
		'RU-ROS' => '21998',
		'RU-STA' => '21756',
		'RU-TVE' => '20726',
		'RU-TUL' => '22133',
		'RU-CE'  => '21813',
		'RU-YAR' => '21408',
		'RU'     => ''
	];

	/* Поля объекта */
	protected $fields = [
		'amenity'         => 'bank',
		'ref'             => '',
		'name'            => 'Московский индустриальный банк',
		'name:ru'         => 'Московский индустриальный банк',
		'name:en'         => 'Moscow Industrial Bank',
		'official_name'   => '',
		'operator'        => 'ПАО "МИнБанк"', // https://www.cbr.ru/credit/coinfo.asp?id=450000741
		'branch'          => '',
		'contact:website' => 'https://www.minbank.ru',
		'contact:phone'   => '+7 800 1007474; +7 495 7400074',
		'opening_hours'   => '',
		'wheelchair'      => 'no',
		'lat'             => '',
		'lon'             => '',
		'_addr'           => '',
		'brand'           => 'Московский индустриальный банк',
		'brand:ru'        => 'Московский индустриальный банк',
		'brand:en'        => 'Moscow Industrial Bank',
		'brand:wikipedia' => 'ru:Московский индустриальный банк',
		'brand:wikidata'  => 'Q4304145'
	];

	/* Фильтр для поиска объектов в OSM */
	protected $filter = [
		'[amenity=bank][name~"индустриальный",i]'
	];

	/* Обновление данных по региону */
	public function update()
	{
		global $RU;
		
		// Класс объекта
		// 1 - банкомат / киоск
		// 10 - офис / терминал
		$class = 10;

		if ($this->region == 'RU') {
			$lat = $RU['RU-MOW']['lat'];
			$lon = $RU['RU-MOW']['lon'];
		} else {
			$lat = $RU[$this->region]['lat'];
			$lon = $RU[$this->region]['lon'];
		}

		$url = 'https://telebank.minbank.ru/geoapi/getTerminals?'
		.'format=JSON'
		.'&class='.$class
		.'&status=1'
		.'&count=1000'
		.'&lat='.$lat
		.'&lon='.$lon;
		//.'max_dist=18536';
		
		$page = $this->get_web_page($url);
		if (is_null($page)) {
			return;
		}

		$this->parse($page);
	}

	/* Парсер страницы */
	protected function parse($st)
	{
		$a = json_decode($st, true);
		if (is_null($a)) {
			return;
		}

		foreach ($a['list'] as $obj) {
			// Исключение терминалов
			if (mb_stripos($obj['name'], 'терминал') !== false) {
				continue;
			}

			// Отсеиваем по региону
			if (($this->region != 'RU') && !$this->isInRegionByCoords($obj['lat'], $obj['lon'])) {
				continue;
			}
			
			// Идентификатор
			if (preg_match('/[Д|О]О "(.+?)"/', $obj['name'], $m)) $obj['ref'] = $m[1];
			else if (preg_match('/[Д|О]О №? ?(.+)/', $obj['name'], $m)) $obj['ref'] = $m[1];
			else if (preg_match('/[Д|О]О "(.+)/', $obj['name'], $m)) $obj['ref'] = $m[1];
			else if (mb_stripos($obj['name'], 'Центральный офис') !== false) $obj['ref'] = 'Центральный офис';
			
			$obj['official_name'] = $obj['name'];
			$obj['_addr'] = $obj['address'];

			// Доступность для инвалидных колясок
			if (mb_stripos($obj['workhours'], 'пандус') !== false) {
				$obj['wheelchair'] = 'yes';
			}

			// Время работы
			$obj['opening_hours'] = $this->time($obj['workhours']);

			// Удаление поля
			unset($obj['name']);
			unset($obj['branch']);

			$this->addObject($this->makeObject($obj));
		}
	}
}
