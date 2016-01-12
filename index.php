<?php
/**
 * csv2import
 * Ver.: 0.4
 * User: Smith
 * Date: 2014.09.01.
 * Time: 16:30
 */

$remove = 		  array('"', '\\', '`', "'"); # mezőkből kicserélendő érvénytelen karakterek
$remove_replace = array('' , ''  , '' , "\'" );
$skip_cleared = false; # érvénytelen karakterek esetén hagyja ki a sort
$skip_empty = false; # üres mező esetén hagyja ki a sort
$allowed = array('csv','txt'); # engedélyezett kiterjesztések
$name_pattern = '[0-9a-zA-Z_]+'; # regex minta tábla és mező nevekhez
$trim_row = true; # trim() a beolvasott soron
$trim_field = true; # trim() a beolvasott mezőn
$test_size = 20;

//TODO:
//$force_encoding = 'UTF-8'; # beolvasott sorok konvertálása
//input_encoding =; # fájl karakter kódolása
// mező specifikus opciók:
//  - trim(alap)/ltrim/rtrim,
//  - 1. egyedi mező checkbox (hagyja ki azokat sorokat ahol adott mező érték már szerepelt)(case sensitive checkbox),
//  - 2. regex megszorítás (sablonok: email/alnum/num),(kihagyja a mintán elbukó mező értékkel rendelkező adott sorokat)
//       --regex alapból mindent enged
//  - sprintf() fix érték a sor kihagyása helyett ha érvénytelen a mező valamelyik feltétel miatt

// teszt feldolgozás X sorral (teszt checkbox)
// info és error tömbként legyen kezelve
//error_reporting(0);
error_reporting(E_ALL ^ E_NOTICE);

define('_UPLOAD', 'upload/');
define('_PROCESS', 'row.php');
$_START = microtime(true);

ini_set('default_charset', 'utf-8');
ini_set('auto_detect_line_endings', true);
//max_file_size, max uploaded files, max post size
session_start();
ignore_user_abort(true);
set_time_limit(0);
$_TITLE = 'csv2import ';

function ext($f) { $temp = explode('.', $f); return strtolower(end($temp)); }

if(!is_readable(_UPLOAD)) $_SESSION['error'] = '/'._UPLOAD.' mappa nem olvasható! ';
elseif(!is_writeable(_UPLOAD)) $_SESSION['error'] = '/'._UPLOAD.' mappa nem írható! ';

if(isset($_GET['reset'])) unset($_SESSION['form']);
elseif(isset($_GET['upload']) && isset($_POST['submit-upload']) && isset($_FILES['csv']))
{
    require_once('upload_handler.php');
}
elseif(isset($_GET['process']) && isset($_POST['submit-process']))
{
    require_once('process_handler.php');
}

$_TABLE = isset($_SESSION['form']['table']) ? htmlspecialchars($_SESSION['form']['table']) : '';
$_FIELD = is_array($_SESSION['form']['field']) ? $_SESSION['form']['field'] : array();
$_FIELD_DEL = isset($_SESSION['form']['field_delimiter']) ? htmlspecialchars($_SESSION['form']['field_delimiter']) : '&#59;';# ;
$_FIELD_ENC = isset($_SESSION['form']['field_enclosure']) ? htmlspecialchars($_SESSION['form']['field_enclosure']) : '&#34;';# "
$_FIELD_ESC = isset($_SESSION['form']['field_escape']) ? htmlspecialchars($_SESSION['form']['field_escape']) : '&#92;';# \
$_CSVFILE = isset($_SESSION['form']['csvfile']) ? $_SESSION['form']['csvfile'] : '';

?><!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?php echo $_TITLE; ?></title>

    <link rel="stylesheet" type="text/css" href="template.css">
    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>

    <!--[if lt IE 9]>
    <script src="html5shiv.js"></script>
    <![endif]-->

</head>
<body>
    <header class="maxwidth">
        <h1 id="logo"><a href="./"><?php echo $_TITLE; ?></a></h1>
        <?php if(!empty($_SESSION['error'])) echo '<div id="error-box">'.$_SESSION['error'].'</div>'; ?>
        <?php if(!empty($_SESSION['info'])) echo '<div id="info-box">'.$_SESSION['info'].'</div>'; ?>
    </header>

    <section class="maxwidth">
        <form id="process-form" action="?process" method="post">
            <div class="form-row"><label>Fájl: </label>
                <select name="csvfile" required>
                    <option value=""></option>
                <?php
                    foreach(scandir(_UPLOAD) as $f)
                    {
                        if(in_array(ext($f), $allowed))
                        {
                            $sel = '';
                            if($_CSVFILE == $f) $sel = ' selected';

                            echo '<option'.$sel.' value="'.htmlspecialchars($f).'">'
                                .htmlspecialchars($f).'</option>';
                        }
                    }
                ?>
                </select>
            </div>
            <div class="form-row"><label>Tábla neve: </label><input pattern="<?php echo $name_pattern; ?>" type="text" name="table" value="<?php echo $_TABLE; ?>" required></div>
            <div class="form-row"><label>Mező nevek sorrendben: </label> <input id="addfield" class="btn" type="button" value="+" onclick="addField()">
                <ol id="fields">
                <?php
                    foreach($_FIELD as $f)
                    {
                        echo '<li><input value="'.htmlspecialchars($f).'" class="field" type="text" name="field[]" pattern="'.$name_pattern.'" required> <input class="removefield btn btn_red btn_small" type="button" value="-" onclick="removeField(this)"></li>';
                    }
                ?>
                </ol>
            </div>
            <div class="form-row"><label>Mező elválasztó: </label>	<input class="onechar" type="text" name="field_delimiter" value="<?php echo $_FIELD_DEL; ?>" maxlength="1"></div>
            <div class="form-row"><label>Mező körülkerítés: </label><input class="onechar" type="text" name="field_enclosure" value="<?php echo $_FIELD_ENC; ?>" maxlength="1"></div>
            <div class="form-row"><label>Mező escape: </label>		<input class="onechar" type="text" name="field_escape" 	  value="<?php echo $_FIELD_ESC; ?>" maxlength="1"></div>
            <div class="form-row"><label>Teszt: </label>			<input type="checkbox" name="test_process" value="1"></div>
            <div class="form-row"><input type="submit" class="btn btn_red" name="reset-process" value="Űrlap törlése" formaction="?reset" formnovalidate> &nbsp; <input type="submit" class="btn" name="submit-process" value="Feldolgozás"></div>
        </form>
        
        <?php if(!isset($_GET['settings'])) { ?>
            <a id="settings-button" href="?settings">Beállítások megjelenítése</a>
        <?php } else { ?>
            <fieldset class="settings">
                <legend class="lft">Beállítások (index.php)</legend>
                <pre><?php
                    function print_bool($v){ if($v===true)return'TRUE';elseif($v===false)return'FALSE';else return $v; }
                    echo substr(str_replace(array('=> Array'),'',print_r(array_map('print_bool', array(
                        '$remove'           => $remove,
                        '$remove_replace'   => $remove_replace,
                        '$skip_cleared'     => $skip_cleared,
                        '$skip_empty'       => $skip_empty,
                        '$allowed'          => $allowed,
                        '$name_pattern'     => $name_pattern,
                        '$trim_row'         => $trim_row,
                        '$trim_field'       => $trim_field,
                        '$test_size'        => $test_size
                    )), true)), 5);
                ?></pre>
            </fieldset>
        <?php } ?>
        
        <?php if(!isset($_GET['row-processor'])) { ?>
            <a id="rowproc-button" href="?row-processor">Sor feldolgozó megjelenítése</a>
        <?php } else { ?>
            <fieldset class="settings">
                <legend class="rgt">Sor feldolgozó szkript (row.php)</legend>
			    <pre><?php
                    $row_php_source = file_get_contents(_PROCESS);
                    echo htmlspecialchars(
                        strstr( strstr( $row_php_source, "\n" ), '/*DEBUG*', true )
                    );
                ?></pre>
            </fieldset>
        <?php } ?>
    </section>

    <section class="maxwidth">
        <h3><label><?php echo '.'.implode(', .', $allowed); ?> fájlok feltöltése: </label></h3>
        <form id="upload-form" action="?upload" method="post" enctype="multipart/form-data">
            <div class="form-row"><input id="csvfile" type="file" name="csv[]" multiple> &nbsp; <input type="submit" class="btn" name="submit-upload" value="Feltöltés"></div>
            <ul><li><?php
                $uploaded = scandir(_UPLOAD);
                natcasesort($uploaded);
                echo implode('</li><li>',$uploaded);
            ?></li></ul>
        </form>
    </section>

    <footer class="maxwidth">
        Futásidő: <?php echo number_format(microtime(true)-$_START, 4, '.', ''); ?>mp
    </footer>

    <script>
        function addField()
        {
            $('#fields').append('<li><input class="field" type="text" name="field[]" pattern="<?php echo $name_pattern; ?>" required> <input class="removefield btn btn_red btn_small" type="button" value="-" onclick="removeField(this)"></li>');
        }
        function removeField(e)
        {
            $(e).parent().remove();
        }
    </script>
</body>
</html>
<?php
unset($_SESSION['info']);
unset($_SESSION['error']);
?>