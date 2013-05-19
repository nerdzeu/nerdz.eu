<?php
/*
 * Classe per la minimizzazione del codice javascript
 */

final class Javascript
{
	public static function optimize($file)
	{
		$ret = array();
		exec("uglifyjs {$file} -c unused=false",$ret);
		return trim(implode("\n",$ret));
	}
}
?>
