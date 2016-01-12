<?php if(!defined('_UPLOAD'))die('*');
/**
 * Created by PhpStorm.
 * User: Smith
 * Date: 2014.09.02.
 * Time: 20:15
 */

//print_r($_FILES);
$temp = multiple($_FILES);
//print_r($temp);

foreach($temp['csv'] as $_F)
{
        if ($_F["error"] > 0)
        {
            $_SESSION['error'] .= 'Feltöltés sikertelen. ('.(empty($_F['name'])?'nem jelöltél ki egy fájlt sem':$_F['name']).')<br/>Hibakód: '.$_F["error"]
                               .' (http://php.net/manual/en/features.file-upload.errors.php)';
        }
        else
        {
            if (in_array(ext($_F['name']), $allowed))
            {
                $_SESSION['info'] .= 'Fájl feltöltve: '
                    .'<br/>Név: ' . $_F["name"]
                    .'<br/>Típus: ' . $_F["type"]
                    .'<br/>Méret: ' . ($_F["size"] / 1024) . ' kB'
                    .'<br/>Temp fájl: ' . $_F["tmp_name"].'<br/>';

                if (file_exists(_UPLOAD.$_F["name"]))
                {
                    clearstatcache();
                    $size = filesize(_UPLOAD.$_F["name"]);
                    $_SESSION['info'] .= '<br/>'.$_F["name"].' ('.($size / 1024).' kB) felül írva!<br/>';
                }

                if(move_uploaded_file($_F["tmp_name"], _UPLOAD.$_F["name"]))
                {
                    $_SESSION['info'] .= '<br/>Eltárolva: <a href="./'
                        ._UPLOAD.$_F["name"].'">/'
                        ._UPLOAD.$_F["name"].'</a><br/><hr/>';
                }
                else
                {
                    $_SESSION['error'] .= 'Hiba a fájl áthelyezése közben. ('.$_F['name'].')<br/>';
                }
            }
            else
            {
                $_SESSION['error'] .= 'Csak .'.implode(', .', $allowed).' kiterjesztésű fájlokat tölthetsz fel!<br/>';
            }
        }
}

/* rekurzív funkció a többfájlos multiselectes $_FILES tömb értelmes formátumba rendezéséhez */
function multiple(array $_files, $top = true)
{
    $files = array();
    foreach($_files as $name=>$file)
    {
        if($top) $sub_file = $file['name'];
        else $sub_file = $name;

        if(is_array($sub_file))
        {
            foreach(array_keys($sub_file) as $key)
            {
                $files[$name][$key] = array(
                    'name'     => $file['name'][$key],
                    'type'     => $file['type'][$key],
                    'tmp_name' => $file['tmp_name'][$key],
                    'error'    => $file['error'][$key],
                    'size'     => $file['size'][$key],
                );

                $files[$name] = multiple($files[$name], false);
            }
        }
        else
        {
            $files[$name] = $file;
        }
    }

    return $files;
}