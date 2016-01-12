<?php if(!defined('_UPLOAD'))die('*');

$sql .= "( '".implode("', '", $rowa)."' )";

/*DEBUG* /
$_SESSION['info'] .= '<pre>'
    .print_r(array(
        $insert.$sql,   # output
        $rowa,          # csv row array
        $l,             # line nr
        $n,             # noticed
        $s              # skipped
    ),true)
    .'</pre>';
$break = true;
/**/