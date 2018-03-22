<?php

class Code
{
	//验证码的个数
	protected $number;
	//验证码的类型
	protected $codeType;
	//宽度
	protected $width;
	//高度
	protected $height;
	//图片的类型
	protected $imageType;
	//验证码
	protected $code;
	//图片资源
	protected $image;

	//初始化成员属性
	function __construct($number = 4, $codeType = 2,$width = 100, $height = 30, $imageType = 'png')
	{
		$this->number = $number;
		$this->codeType = $codeType;
		$this->width = $width;
		$this->height = $height;
		$this->imageType = $imageType;
		//调用生成验证码的函数
		$this->code = $this->getCode();
	}
	//生成验证码字符串的函数
	protected function getCode()
	{
		switch ($this->codeType) {
			//纯数字
			case 0:
				$code = $this->getNumberCode();
				break;
			case 1:
				$code = $this->getCharCode();
				break;
			case 2:
				$code = $this->getNumCharCode();
				break;
			default :
				exit('不支持的类型');
		}
		return $code;
	}
	//数字的验证码
	protected function getNumberCode()
	{
		$str = join('', range(0, 9));
		return substr(str_shuffle($str), 0, $this->number);
	}
	//字母的验证码
	protected function getCharCode()
	{
		$arr = range('a', 'z');
		$str = join('', $arr);
		$str .= strtoupper($str);
		return substr(str_shuffle($str), 0, $this->number);
	}
	//结合
	protected function getNumCharCode()
	{
		$str = '';
		for ($i=0; $i < $this->number; $i++) { 
			$t = mt_rand(0,2);
			switch ($t) {
				case 0:
					$str .= chr(mt_rand(48, 57));
					break;
				case 1:
					$str .= chr(mt_rand(65, 90));
					break;
				case 2:
					$str .= chr(mt_rand(97, 122));
					break;
				
			}
		}
		return $str;
	}
	//当外部读取code字符串的额，允许读取
	function __get($name)
	{
		if ($name == 'code') {
			return $this->code;
		}
		return false;
	}
	public function outImage()
	{
		//生成画布
		$this->image = $this->createImage();
		//填充背景颜色
		$this->fillBackground();
		//写验证码
		$this->drawCode();
		//画干扰元素
		$this->drawDisturb();
		//输出显示到浏览器
		$this->show();
	}
	protected function createImage()
	{
		return imagecreatetruecolor($this->width, $this->height);
	}
	protected function fillBackground()
	{
		imagefill($this->image,0, 0, $this->lightColor());
	}
	protected function lightColor()
	{
		return imagecolorallocate($this->image, mt_rand(130,255), mt_rand(130,255), mt_rand(130,255));
	}
	protected function darkColor()
	{
		return imagecolorallocate($this->image, mt_rand(0,120), mt_rand(0,120), mt_rand(0,120));
	}
	protected function drawCode()
	{
		for ($i=0; $i < $this->number; $i++) { 
			$c = $this->code[$i];
			$width = ceil($this->width / $this->number);
			$x = mt_rand($i * $width + 10, ($i + 1) * $width - 15);
			$y = mt_rand(0, $this->height - 15);
			imagechar($this->image , 5, $x, $y, $c, $this->darkColor());
		}
	}
	protected function drawDisturb()
	{
		for ($i=0; $i < $this->width * $this->height / 8; $i++) { 
			$x = mt_rand(0, $this->width);
			$y = mt_rand(0, $this->height);
			imagesetpixel($this->image, $x, $y, $this->darkColor());
		}
	}
	protected function show()
	{
		header('Content-Type:image/'. $this->imageType);
		//拼接一下函数名字
		$func = 'image' . $this->imageType;
		$func($this->image);
	}
}
$code = new Code();
$code->outImage();