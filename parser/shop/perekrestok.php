<?php
require_once $_SERVER["DOCUMENT_ROOT"].'/common/Validator.class.php';

class perekrestok extends Validator
{
	protected $domain = 'https://www.perekrestok.ru/api/shops/?city=';

	static $urls = [
		'RU-MOW' => ['410', '109', '624', '170', '249', '539', '581'], // Москва, Внуково, Железнодорожный, Зеленоград, Климовск, Троицк, Щербинка
		'RU-ROS' => ['37', '396', '179', '523'], // Азов, Новочеркасск, Ростов-на-Дону, Таганрог
		'RU-TA'  => ['63', '196', '323', '374'], // Альметьевск, Казань, Набережные Челны, Нижнекамск
		'RU-SAR' => ['26', '457', '587'], // Балаково, Саратов, Энгельс
		'RU-MOS' => [
			'28', '70', '95', '92', '627', '612', '611', '615', '165', '281', '251', '248', '264',
			'261', '223', '315', '426', '327', '379', '334', '451', '350', '316', '413', '345', '172',
			'188', '513', '515', '486', '503', '501', '559', '549', '650', '582', '585'
		],
		// Балашиха, Видное, Волоколамск, Голицыно, Дедовск, Дмитров, Долгопрудный, Дубна, Жуковский, Звенигород, Истра, Кимры, Клин, Коломна, Королев, Красногорск, Люберцы, Мытищи, Наро-Фоминск, Ногинск, Обнинск, Одинцово, Орехово-Зуево, п. Лесной Городок, Подольск, Протвино, Раменское, Реутов, Сергиев Посад, Серпухов, Солнечногорск, Ступино, Сходня, Томилино, Химки, Чехов, Щелково, Электросталь
		'RU-BEL' => ['140'], // Белгород
		'RU-NIZ' => ['40', '79', '128', '88', '604', '618', '160', '370', '458'], // Арзамас, Бор, Выкса, Городец, Дзержинск, Заволжье, Нижний Новгород, Саров
		'RU-VLA' => ['56'], // Владимир
		'RU-ME'  => ['102', '279'], // Волжск, Йошкар-Ола
		'RU-VOR' => ['86'], // Воронеж
		'RU-LEN' => ['114', '126', '489'], // Всеволожск, Выборг, Сосновый Бор
		'RU-KDA' => ['150', '230', '382', '491'], // Геленджик, Краснодар, Новороссийск, Сочи
		'RU-SVE' => ['594'], // Екатеринбург
		'RU-KLU' => ['203'], // Калуга
		'RU-KRS' => ['288'], // Курск
		'RU-LIP' => ['253'], // Липецк
		'RU-CHE' => ['322', '649'], // Магнитогорск, Челябинск
		'RU-STA' => ['360', '453', '492'], // Минеральные Воды, Пятигорск, Ставрополь
		'RU-TOM' => ['373'], // Нижневартовск
		'RU-SAM' => ['384', '463', '505', '557'], // Новокуйбышевск, Самара, Сызрань, Тольятти
		'RU-TUL' => ['387', '569'], // Новомосковск, Тула
		'RU-ORL' => ['348'], // Орел
		'RU-ORE' => ['349'], // Оренбург
		'RU-SPE' => ['465', '521', '418'], // Санкт-Петербург, Сестрорецк, Отрадное
		'RU-PNZ' => ['437'], // Пенза
		'RU-PER' => ['433'], // Пермь
		'RU-RYA' => ['189'], // Рязань
		'RU-MO'  => ['455'], // Саранск
		'RU-KHM' => ['499', '665'], // Сургут, Югорск
		'RU-TAM' => ['530'], // Тамбов
		'RU-TVE' => ['534'], // Тверь
		'RU-TYU' => ['553', '579'], // Тобольск, Тюмень
		'RU-ULY' => ['552'], // Ульяновск
		'RU-BA'  => ['567'], // Уфа
		'RU-CU'  => ['638'], // Чебоксары
		'RU-YAR' => ['669'], // Ярославль
		'RU'     => ['0'] // Россия
	];

	/* Поля объекта */
	protected $fields = [
		'shop'            => 'supermarket',
		'name'            => 'Перекрёсток',
		'name:ru'         => 'Перекрёсток',
		'name:en'         => 'Perekryostok',
		'operator'        => 'ЗАО "Торговый дом Перекрёсток"',
		'contact:website' => 'https://www.perekrestok.ru',
		'contact:phone'   => '',
		'opening_hours'   => '',
		'lat'             => '',
		'lon'             => '',
		'_addr'           => '',
		'brand:wikidata'  => 'Q1684639',
		'brand:wikipedia' => 'ru:Перекрёсток (сеть магазинов)'
	];

	/* Фильтр для поиска объектов в OSM */
	protected $filter = [
		'[shop=supermarket][name~"Перекр[её]сток",i]'
	];

	/* Обновление данных по региону */
	public function update()
	{
		$this->log('Обновление данных по региону '.$this->region.'.');

		$url = $this->domain;
		$cities = static::$urls[$this->region];

		foreach ($cities as $city) {
			$page = $this->get_web_page("$url$city");
			if (is_null($page)) {
				return;
			}
			$this->parse($page);
		}
	}

	/* Парсер страницы */
	protected function parse($st)
	{
		$st = json_decode($st, true);
		if (is_null($st)) {
			return;
		}

		foreach ($st as $obj) {
			$obj['_addr'] = strip_tags($obj['address']);

			$obj['contact:phone'] = $this->phone($obj['tel']);

			// Время работы
			if ($obj['time']['is_24'] == true) {
				$obj['opening_hours'] = '24/7';
			} else {
				$obj['opening_hours'] = $obj['time']['open'].'-'.$obj['time']['close'];
			}

			list($obj['lat'], $obj['lon']) = explode(',', $obj['coordinates']);

			$this->addObject($this->makeObject($obj));
		}
	}
}
