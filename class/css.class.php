<?php
/*
 * Classe per la minimizzazione del codice css
 */
final class Css
{
	public static function optimize($file)
	{
		exec("csstidy {$file} --allow_html_in_templates=false --compress_colors=true --compress_font-weight=true --remove_last_\;=true --remove_bslash=true --template=highest --preserve_css=true --silent=true",$ret);
		return trim(implode("\n",$ret));
	}
}
?>
