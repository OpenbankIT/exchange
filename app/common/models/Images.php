<?php

namespace App\Models;

class Images extends ModelBase
{

	public static function exists($full_path)
	{
		if (empty($full_path) || !file_exists($full_path)) {
			return false;
		}

		$img_md5 = md5_file($full_path);
		$image = self::findFirst(array(
        	'img_md5 = :img_md5:',
            'bind' => array('img_md5' => $img_md5)
        ));

		if (!empty($image)) {
			return $image->img_id;
		}

        return false;
	}

	public static function insert($path, $full_path)
	{
		if (empty($path) || empty($full_path) || !file_exists($full_path)) {
			return false;
		}

		$img_md5 = md5_file($full_path);
		$image = self::findFirst(array(
        	'img_md5 = :img_md5:',
            'bind' => array('img_md5' => $img_md5)
        ));

		if (!empty($image)) {
			if ($image->img_path != $path) {
				unlink($full_path);
			}
			
			return $image->img_id;
		}

		$image = new Images();
		$image->img_path 		= $path;
		$image->img_md5 		= md5_file($full_path);
		$image->img_compressed  = new \Phalcon\Db\RawValue('default');
		$image->img_added 		= new \Phalcon\Db\RawValue('default');

		if ($image->create()) {
			return $image->img_id;
		}

		return false;
	}
}