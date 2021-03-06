<?php namespace fw\captcha\driver;

/**
* GD-капча
*/
class gd
{
	private $fonts;
	private $fonts_dir;
	private $height;
	private $width;
	
	function __construct($fonts_dir, array $fonts)
	{
		$this->fonts_dir = $fonts_dir;
		$this->fonts     = $fonts;
	}
	
	/**
	* Вывод капчи
	*/
	public function send($code)
	{
		$font_file = $this->fonts_dir . '/' . $this->fonts[mt_rand(0, sizeof($this->fonts) - 1)];
		
		$this->set_dimensions(180, 40);
		
		$image = imagecreatetruecolor($this->width, $this->height);
		
		imagealphablending($image, false);
		imagesavealpha($image, true);
		
		$colorbg = imagecolorallocatealpha($image, 0, 0, 0, 127);
		imagefilledrectangle($image, 0, 0, $this->width, $this->height, $colorbg);
		imagecolordeallocate($image, $colorbg);
		
		imagealphablending($image, true);

		for ($i = 0, $posx = 8, $posy = 28, $len = mb_strlen($code); $i < $len; $i++) {
			$colortext = imagecolorallocatealpha($image, mt_rand(0, 200), mt_rand(0, 200), mt_rand(0, 200), mt_rand(0, 63));
			$font_size = mt_rand(18, 23);
			$font_angle = mt_rand(-15, +15);

			imagettftext($image, $font_size, $font_angle, $posx, $posy + rand(-2, +2), $colortext, $font_file, mb_substr($code, $i, 1));
			imagecolordeallocate($image, $colortext);
			
			$dim = imagettfbbox($font_size, $font_angle, $font_file, mb_substr($code, $i, 1));
			$posx += $dim[2] - $dim[0] + ($font_size > 20 ? 6 : 12);
		}

		header('Content-type: image/png');
		imagepng($image);
		imagedestroy($image);
	}
	
	/**
	* Размеры капчи
	*/
	public function set_dimensions($width, $height)
	{
		$this->height = $height;
		$this->width = $width;
	}
}
