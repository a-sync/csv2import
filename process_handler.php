<?php if(!defined('_UPLOAD'))die('*');
/**
 * Created by PhpStorm.
 * User: Smith
 * Date: 2014.09.02.
 * Time: 21:40
 */

if($_POST['table'] == '' || !is_array($_POST['field'])) $_SESSION['error'] .= 'A tábla név, és legalább egy mező név szükséges!';
else
{
    $_SESSION['form']['table'] = $_POST['table'];
    $_SESSION['form']['field'] = $_POST['field'];
    $_SESSION['form']['field_delimiter'] = $_POST['field_delimiter'];
    $_SESSION['form']['csvfile'] = $_POST['csvfile'];

    $fieldsnum = count($_POST['field']);
    $insert = 'INSERT INTO '.$_POST['table'].' ( `'.implode('`, `' ,$_POST['field']).'` ) VALUES '.PHP_EOL;

    $csvfile = _UPLOAD.$_POST['csvfile'];
    if(!is_readable($csvfile)) $_SESSION['error'] .= 'A fájl nem olvasható! ('.$csvfile.')';
    else
    {
        if(ext($csvfile) == 'csv' || ext($csvfile) == 'txt')
        {
            $l = 0;//line nr
            $n = 0;//noticed
            $s = 0;//skipped
            $sql = false;
            $sqlfile = substr($csvfile, 0, -4).($_POST['test_process']==1?'.test':'').'.sql';

            file_put_contents($sqlfile, $insert);

            $file = fopen($csvfile, 'r');
            while(!feof($file))
            {
                ++$l;
                if($_POST['test_process'] == 1 && $l > $test_size) break;
				
                $row = fgets($file);
                if($trim_row != false) $row = trim($row);

                //if($force_encoding != false) $row = iconv(mb_detect_encoding($row, mb_detect_order(), true), $force_encoding, $row);
                //if($force_encoding != false) $row = mb_convert_encoding($row, $force_encoding, mb_detect_encoding($row, mb_detect_order(), true));

                //$rowa = explode($_POST['field_delimiter'], $row);
				$rowa = str_getcsv($row, $_POST['field_delimiter'], $_POST['field_enclosure'], $_POST['field_escape']);
				
                if(count($rowa) != $fieldsnum) $_SESSION['error'] .= '<div>'.++$s.'. Mezők száma nem megfelelő! ('.$fieldsnum.' helyett '.count($rowa).') <pre>'.$l.'. sor: '.$row.'</pre></div>';
                else
                {
                    $rowa = str_replace($remove, $remove_replace, $rowa, $count);
                    if(!empty($count)) $_SESSION[(!$skip_cleared?'info':'error')] .= '<div>'.(!$skip_cleared?++$n:++$s).'. Érvénytelen karakter(ek) a mezőkben. ('.$count.' db) <pre>'.$l.'. sor: '.$row.'</pre></div>';

                    if(empty($count) || !$skip_cleared)
                    {
						if($trim_field)
						{
							foreach($rowa as $ti => $tf) $rowa[$ti] = trim($tf);
						}
						
                        $empty = $fieldsnum - count(array_filter($rowa));
                        if($empty > 0) $_SESSION[(!$skip_empty?'info':'error')] .= '<div>'.(!$skip_empty?++$n:++$s).'. Üres mezők a sorban. ('.$empty.' db) <pre>'.$l.'. sor: '.$row.'</pre></div>';

                        if($empty <= 0 || !$skip_empty)
                        {
                            if($sql === false) $sql = '';
                            else $sql = ', '.PHP_EOL;

                            if((@include(_PROCESS)) === false) {
                                if($l == 1) $_SESSION['error'] .= '<div><b>'._PROCESS.' betöltése sikertelen.</b></div>';
                                $sql .= "( '".implode("', '", $rowa)."' )"; # default
                            }

                            file_put_contents($sqlfile, $sql, FILE_APPEND);
                            if(isset($break)) break;
                        }
                    }
                }
            }
            fclose($file);
        }
        else $_SESSION['error'] .= '<div>A kapott fájl kiterjesztése ismeretlen.</div>';

        $_SESSION['info'] .= (!$_SESSION['info']?'':'<br/>').'<div>Feldolgozás kész.<br/><a target="_blank" href="'.$sqlfile.'">'.$sqlfile.' ('.(filesize($sqlfile) / 1024).' kB)</a></div>';
    }
}