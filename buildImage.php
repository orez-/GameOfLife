<?php
//error_reporting(E_STRICT);
header( "Content-type: image/gif" );
//set_time_limit(0);

require_once("load.php");
require_once("GIFEncoder.class.php");

function tick(&$board,$r,$c)
{
    $save = array();
    $len = sizeof($board);
    
    for($i=0; $i<$len; $i++)
    {
        $neigh = 0;
        for($j=-$c; $j<=$c; $j+=$c)       // vertical
            for($k=-($i%$c!=0?1:0); $k<=($i%$c!=$c-1?1:0); $k++)    // horizontal
                if(!($k==0 && $j==0) && $i+$j+$k>=0 && $i+$j+$k<$len && $board[$i+$j+$k]) // alive and not yourself
                    $neigh++;            // a neighbor!
        $living = ($neigh==3 || ($neigh==2 && $board[$i]));
        array_push($save, $living);
    }
    $board = $save;
}

function buildFrame($board, $r, $c, $scale, &$arr)
{
    $my_img = imagecreate($r*$scale, $c*$scale);
    $background = imagecolorallocate( $my_img, 0xCC, 0xCC, 0xCC );
    $cell_color = imagecolorallocate($my_img, 0,128,0);
    imagesetthickness ( $my_img, $scale);
    for($i=0; $i<$r*$c; $i++)
        if($board[$i])
        {
            $x = ($i%$c)*$scale;
            $y = floor($i/$c)*$scale;
            imagefilledrectangle($my_img, $x, $y, $x+$scale-1, $y+$scale-1, $cell_color);
        }
    ob_start();
    imagegif($my_img);
    array_push($arr, ob_get_contents());
    ob_end_clean();
    //imagecolordeallocate($my_img, $cell_color);
    //imagecolordeallocate($my_img,  $background );
    //imagedestroy( $my_img );
}

if(!isset($_GET["r"]) || !isset($_GET["c"]) || !isset($_GET["load"]) || !isset($_GET["record"]))
{
    $my_img = imagecreate(220, 70);
    $background = imagecolorallocate( $my_img, 0xCC, 0xCC, 0xCC );
    $text_color = imagecolorallocate( $my_img, 0, 128, 0);
    imagestring( $my_img, 4, 10, 25, "Invalid url, knucklehead.", $text_color );
    imagegif($my_img);
    imagecolordeallocate($my_img, $text_color);
    imagecolordeallocate($my_img,  $background );
    die();
}

$record = $_GET["record"];
$r = $_GET["r"];
$c = $_GET["c"];
$load = $_GET["load"];
$scale = 10;
if(isset($_GET["scale"]))
    $scale = $_GET["scale"];

$bin = array();
$board = decode($load, $r*$c);
for($i=0; $i<$record; $i++)
{
    buildFrame($board, $r,$c, $scale, $bin);
    tick($board, $r, $c);
}

$gif = new GIFEncoder(
    $bin, // binary data array
    array_fill(0,$record,20), //delay times - int
    0, //Animation loops - int - 0 is infinite
    2, //Disposal - int
    -1, -1, -1, //transparency red, green, blue - int
    "bin" // source type
);
echo $gif->GetAnimation();
?>
