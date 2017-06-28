<?php

namespace App\Lib;

class Tools
{
	public static function dk2Latin($text)
	{
		if (empty($text)) {
			return false;
		}

		$replace = array(
            'å' => 'aa',            'Å' => 'aa',
            'æ' => 'ae',            'Æ' => 'ae',
            'é' => 'e',             'É' => 'e',
            'ø' => 'oe',            'Ø' => 'oe',
            'á' => 'a',             'Á' => 'a',
            'à' => 'a',             'À' => 'a',
            'â' => 'a',             'Â' => 'a',
            'ä' => 'a',             'Ä' => 'a',
            'ô' => 'o',             'Ô' => 'o',
            'ó' => 'o',             'Ó' => 'o',
            'ò' => 'o',             'Ò' => 'o',
            'ö' => 'o',             'Ö' => 'o',
            'é' => 'e',             'É' => 'e',
            'è' => 'e',             'È' => 'e',
            'ê' => 'e',             'Ê' => 'e',
            'ë' => 'e',             'Ë' => 'e',
        );

        return str_replace(array_keys($replace), $replace, $text);
	}

	public static function slugify($text, $limit = null)
	{
		$text = html_entity_decode($text);
		$text = strtolower($text);

        $text = self::dk2Latin($text);

		$text = preg_replace('~[^\\pL\d]+~u', '-', $text);
		$text = trim($text, '-');
		$text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
		$text = preg_replace('~[^-\w]+~', '', $text);

		if (empty($text)){
			return false;
		}

		if (!empty($limit)) {
			$text = substr($text, 0, $limit-1);
		}

		return $text;
	}

	public static function readMore($string, $limit = 300, $more_text = '...')
	{
		if (empty($string)) {
			return false;
		}

		if (strlen($string) > $limit) {
			return substr($string, 0, $limit) . '...';
		}

		return $string;
	}

	public static function getDate($date)
	{
		switch ($date) {
			case 'today':
			default:
				$date = date('Y-m-d 00:00:00');
				break;

			case 'week':
				$date = date('Y-m-d 00:00:00', strtotime('last monday', strtotime('tomorrow')));
				break;

			case 'month':
				$date = date('Y-m-01 00:00:00');
				break;
		}

		return $date;
	}

	public static function randString($length = 30)
	{
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	    $charactersLength = strlen($characters) - 1;
	    $randomString = '';
	    for ($i = 0; $i < $length; $i++) {
	        $randomString .= $characters[rand(0, $charactersLength)];
	    }

	    return $randomString;
	}

	public static function buildPaymentConfig($config){

	    if (!empty($config->cashier) && !empty($config->horizon)) {

            return (object)[
                'emission' => (object)[
                    'username' => $config->cashier->username,
                    'password' => $config->cashier->password,
                    'url'      => $config->cashier->url
                ],
                'horizon' => (object)[
                    'host' => $config->horizon->host,
                    'port' => $config->horizon->port
                ],

            ];
        }

        return false;

    }
}