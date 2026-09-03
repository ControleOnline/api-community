<?php
namespace App\Library\Utils\PDF\HTML2PDF;


class PDFGenerator
{
	public function __construct($_html)
  {
		// main vars
		$this->html       = $_html;
		$this->title      = '';
		$this->articleurl = '';
		$this->author     = '';
		$this->date       = time();
		// other options
		$this->from       = 'iso-8859-1';
		$this->to         = 'UTF-8';
		$this->useiconv   = false;
		$this->bi         = true;
	}

	public function _convert($s)
  {
		if ($this->useiconv)
			return iconv($this->from, $this->to, $s);
		else
			return $s;
	}

	public function getAsString(): string
  {
		// change some win codes, and xhtml into html
		$str=[
  		'<br />'  => '<br>',
  		'<hr />'  => '<hr>',
  		'[r]'     => '<red>',
  		'[/r]'    => '</red>',
  		'[l]'     => '<blue>',
  		'[/l]'    => '</blue>',
  		'&#8220;' => '"',
  		'&#8221;' => '"',
  		'&#8222;' => '"',
  		'&#8230;' => '...',
  		'&#8217;' => '\''
    ];

		foreach ($str as $_from => $_to)
      		$this->html = str_replace($_from, $_to, $this->html);

		$pdf = new PDF('P', 'mm', 'A4', $this->title, $this->articleurl, false);
		$pdf->AddPage();

		// html
		$pdf->WriteHTML($this->_convert(stripslashes($this->html)), $this->bi);
		

		// output
		return $pdf->Output('S');

	}
}
