<?php
define("alpha", "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz_-");

function decode($str,$len)
{
    $board = array();
    $end = strlen($str);
    
    for($i=0; $i<$end; $i++)
    {
        $char = $str{$i};
        if($char != "{" && $char != "}")
        {
            $char = strpos(alpha, $char);
            for($j=0; $j<6; $j++)
                array_push($board, (bool)($char&pow(2,$j)));
        }
        else
        {
            $char = ($char=="{"?"0":"-");
            $end2 = strpos(alpha, $str{++$i})+3;
            for($k=0; $k<$end2; $k++)
                for($j=0; $j<6; $j++)
                    array_push($board, $char);
        }
    }
    for($i*=6;$i<$len;$i++)     // fill the rest
        array_push($board,0);
    return array_slice($board,0,$len);
}
?>
