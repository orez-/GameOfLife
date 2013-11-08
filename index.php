<?php
require_once("load.php");

function printBoard($board)
{
    if(!sizeof($board))
    {
        echo "[]";
        return;
    }
    $toR = "[";
    foreach($board as $i=>$v)
        $toR .= ($v?1:0).",";
    return substr($toR,0,-1)."]";
}

function loadPage()
{
    $load = "0";
    $r = 10;
    $c = 10;
    if(isset($_GET['r']) && isset($_GET['c']))
    {
        if(isset($_GET['load']))
        {
            $load = $_GET['load'];
            if(!preg_match("/^[}{".alpha."]*\$/",$load))
            {
                echo "alert('Malformed load data: $load');";
                $load = "0";
            }
        }
        $r = $_GET['r'];
        $c = $_GET['c'];
    }
    $pT = "            ";   // properTabbage
    $toR = "";
    $toR .= "var ALPHA = \"".alpha."\";\n";
    $toR .= $pT."var r = $r;\n";
    $toR .= $pT."var c = $c;\n";
    $toR .= $pT."var board = ".printBoard(decode($load,$r*$c)).".slice(0,r*c);   // trust me, this looks better in the php source code\n";
    echo $toR;
    return true;
}

?>
<html>
    <head>
        <title>Conway's Game of Life</title>
        <style>
            #board td
            {
                width:15;
                height:15;
                font-size:0pt;;
            }
            #tabBar td
            {
                -moz-border-radius-topright: 10px;
                border-top-right-radius: 10px;
                -moz-border-radius-topleft: 10px;
                border-top-left-radius: 10px;
                background-color:darkgray;
                cursor:pointer;
                cursor:hand;
            }
            td
            {
                text-align:center
            }
            .thin
            {
                width:30;
            }
            #tabbedTable
            {
                width:250;
            }
        </style>
        <script>
            <?php loadPage(); ?>
            var DEAD = "lightgray";
            var ALIVE = "green";
            var interval = null;
            var lastRun = null;
            var tabClicked = 0;
            var lastColor = null;
            
            function encodeBoard()
            {
                var toR = "";
                for(var i=0; i<board.length; i+=6)
                {
                    var num = 0;
                    for(var j=0; j<6; j++)
                        if(board[i+j])
                            num += Math.pow(2,j);
                    toR += ALPHA[num];
                }
                toR = toR.replace(/^(0?.*?)0+$/,"$1");  // remove trailing 0s
                toR = toR.replace(/0{3,66}|-{3,66}/g, function(mtch)
                    {return (mtch[0]=="0"?"{":"}")+ALPHA[mtch.length-3];});
                return toR;
            }
            
            function toggleColor(somewhere, aliveColor, ignoreBoard, set)
            {
                if(!somewhere.id)
                    somewhere = document.getElementById(somewhere);
                var id = parseInt(somewhere.id.substring(1));
                if(arguments.length < 3)
                    set = !board[id];
                if(!ignoreBoard)
                    board[id] = set;
                if(!isNaN(aliveColor))
                {
                    aliveColor = aliveColor.toString(16);
                    while(aliveColor.length < 6)
                        aliveColor = "0"+aliveColor;
                    aliveColor = "#"+aliveColor;
                }
                somewhere.style.backgroundColor = (set?aliveColor:DEAD);
            }
            
            function printBoard()
            {
                var toR = "";
                for(var i=0; i<r; i++)
                {
                    toR += "<tr>";
                    for(var j=0; j<c; j++)
                    {
                        var ind = i*c+j;
                        toR += "<td id='s"+ind+"' onClick='toggleColor(this, ALIVE);' style='background-color:"+(board[ind]?ALIVE:DEAD)+"'>&nbsp;</td>";
                    }
                    toR += "</tr>";
                }
                document.getElementById("board").innerHTML = toR;
            }
            
            function randomColor()
                {return (Math.floor(Math.random()*0xCC)<<16) + (Math.floor(Math.random()*0xCC)<<8) + (Math.floor(Math.random()*0xCC));}
            
            function gradient()
            {
                if(!lastColor)
                {
                    lastColor = 0xFF0000;
                    return lastColor.toString(16);
                }
                var re = lastColor >> 16;
                var gr = (lastColor >> 8)%256;
                var bl = lastColor%256;
                var amt = 0x10;
                
                re = Math.min(0xFF, re+(re<0xFF && gr==0 && bl==0xFF)*amt);
                gr = Math.min(0xFF, gr+(re==0xFF && gr<0xFF && bl==0)*amt);
                bl = Math.min(0xFF, bl+(re==0 && gr==0xFF && bl<0xFF)*amt);
                
                re = Math.max(0, re-(re>0 && gr==0xFF && bl==0)*amt);
                gr = Math.max(0, gr-(re==0 && gr>0 && bl==0xFF)*amt);
                bl = Math.max(0, bl-(re==0xFF && gr==0 && bl>0)*amt);
                
                lastColor = (re<<16) + (gr<<8) + bl;
                var toR = lastColor.toString(16);
                while(toR.length < 6)
                    toR = "0"+toR;
                return toR;
            }
            
            function tick()
            {
                var cm1 = parseInt(document.getElementById("colormode1").value);
                var cm2 = parseInt(document.getElementById("colormode2").value);
                var cm3 = parseInt(document.getElementById("colormode3").value);
                var aliveColor = (cm1==1?randomColor():(cm1==2 && cm2==1?gradient():ALIVE));
                var save = [];
                var identical = Boolean(interval);  // might need to stop, if running
                for(var i=0; i<board.length; i++)
                {
                    var neigh = 0;
                    for(var j=-c; j<=c; j+=c)       // vertical
                        for(var k=-(i%c!=0); k<=(i%c!=c-1); k++)    // horizontal
                            if(!(k==0 && j==0) && board[i+j+k]) // alive and not yourself
                                neigh++;            // a neighbor!
                    var living = (neigh==3 || (neigh==2 && board[i]));
                    save.push(living);
                    if(board[i] != living)
                        identical = false;
                    if(board[i] != living || cm3)
                        toggleColor("s"+i, (cm2==1 || cm1==0?aliveColor:(cm1==2?gradient():randomColor())), true, living);
                }
                if(identical)
                    sim_stop();
                board = save;
            }
            
            function sim_run()
            {
                lastRun = encodeBoard();
                var button = document.getElementById("runstop");
                button.value = "Stop";
                button.removeEventListener("click", sim_run, false);
                button.addEventListener("click", sim_stop, false);
                interval = window.setInterval(tick, 200);
            }
            
            function sim_stop()
            {
                interval = window.clearInterval(interval);
                var button = document.getElementById("runstop");
                button.value = "Run";
                button.removeEventListener("click", sim_stop, false);
                button.addEventListener("click", sim_run, false);
            }
            
            function sim_clear()
            {
                for(var i=0; i<r*c; i++)
                    toggleColor("s"+i, ALIVE, false, false);
            }
            
            function saveSetup(encoding)
            {
                var toR = (window.location.href+"").replace(/\?.*$/,"");
                document.getElementById("saveData").value = toR+"?load="+(encoding?encoding:encodeBoard())+"&r="+r+"&c="+c;
            }
            
            function ajaxCall(url, func)
            {
                var xmlhttp = ((window.XMLHttpRequest)?(xmlhttp=new XMLHttpRequest()):(xmlhttp=new ActiveXObject("Microsoft.XMLHTTP")));
                xmlhttp.onreadystatechange=function()
                {
                    if (xmlhttp.readyState==4 && xmlhttp.status==200)
                    {
                        func(xmlhttp.responseText);
                    }
                }
                xmlhttp.open("GET",url,true);
                xmlhttp.send();
                return true;
            }
            
            function resize(rows, cols)
            {
                rows = parseInt(rows)-r;
                cols = parseInt(cols)-c;
                var center = 5;
                var boxes = document.getElementsByName("resizeAnchor");
                for(var i=0; i<9; i++)
                    if(boxes[i].checked)
                    {
                        center = boxes[i].value;
                        break;
                    }
                var halign = (center%3)-1;
                var valign = Math.floor(center/3)-1;
                
                var args = [];
                var zeroes = fill(c*rows, 0);  // a row
                
                if(valign != 1)
                {
                    var cut = Math.max(0,-c*Math.ceil(valign?rows:rows/2));
                    args = [r*c-cut, cut].concat(zeroes.slice(valign?0:c*Math.ceil(rows/2))); // add rows to the bottom
                    Array.prototype.splice.apply(board, args);
                }
                if(valign != -1)
                {
                    args = [0, Math.max(0,-c*Math.floor(valign?rows:rows/2))].concat(zeroes.slice(valign?0:c*Math.floor(rows/2)));   // add rows to the top
                    Array.prototype.splice.apply(board, args);
                }
                r += rows;
                
                zeroes = fill(cols, 0);
                for(var i=r; i>0; i--)
                {
                    if(halign != 1)
                    {
                        var cut = Math.max(0,-Math.floor(halign?cols:cols/2));
                        args = [i*c-cut, cut].concat(zeroes.slice(halign?0:Math.floor(cols/2))); // add cols to the right
                        Array.prototype.splice.apply(board, args);
                    }
                    if(halign != -1)
                    {
                        args = [(i-1)*c, Math.max(0,-Math.ceil(halign?cols:cols/2))].concat(zeroes.slice(halign?0:Math.ceil(cols/2))); // add cols to the left
                        Array.prototype.splice.apply(board, args);
                    }
                }
                c += cols;
                
                printBoard();
            }
            
            function loadUp()
            {
                document.getElementById("runstop").addEventListener("click", sim_run, false);
                document.getElementById("nrows").value = r;
                document.getElementById("ncols").value = c;
                printBoard();
            }
            
            function fill(amt, val)
            {
                var ar = [];
                for(var i=0; i<amt; i++)
                    ar.push(val);
                return ar;
            }
            
            function clickTab(which)
            {
                var id = which.id.substring(3);
                if(id == tabClicked)
                    return;
                document.getElementById("tab"+tabClicked).style.backgroundColor = "darkgray";
                document.getElementById("tab"+tabClicked).style.fontWeight = "normal";
                which.style.backgroundColor = "white";
                which.style.fontWeight = "bold";
                document.getElementById("tabSel"+tabClicked).style.display = "none";
                document.getElementById("tabSel"+id).style.display = "";
                tabClicked = id;
            }
            function openImage()
            {
                var record = document.getElementById("record").value;
                window.open( "buildImage.php?load="+encodeBoard()+"&r="+r+"&c="+c+"&record="+record );
            }
        </script>
    </head>
    <body onLoad="loadUp();" bgcolor="gray">
        <center><table id="board"></table>
        <tr><td colspan="2"><input tabindex="1" id="tick"    type="button" value="Step" onClick="tick();"></td>
            <td colspan="2"><input tabindex="2" id="runstop" type="button" value="Run"></td>
            <td colspan="2"><input tabindex="3" id="clear"   type="button" value="Clear" onClick="sim_clear();"></td>
        </tr>
        <table id="tabbedTable" cellpadding="0" cellspacing="0">
        <tr id="tabBar"><td onClick="clickTab(this);" id="tab0" style="background-color:white;font-weight:bold;">Color</td><td onClick="clickTab(this);" id="tab1">Save/Load</td><td onClick="clickTab(this);" id="tab2">Resize</td></tr>
        <tr><td style="background-color:white;" colspan="3">
        <center>
            <table><tr id="tabSel0" style="display:"><td>
                <table><tr>
                           <td><select id="colormode1">
                               <option value="0">Single Color</option>
                               <option value="1">Random</option>
                               <option value="2">Gradient</option>
                           </select></td>
                           <td><select id="colormode2">
                               <option value="0">Per Cell</option>
                               <option value="1">Per Tick</option>
                           </select></td>
                           <tr><td colspan="2"><select id="colormode3">
                               <option value="0">New Cells</option>
                               <option value="1">All Cells</option>
                           </select></td></tr>
                </tr></table>
                </td></tr><tr><td><table id="tabSel1" style="display:none"><tr>
                           <td><input id="simsave" type="button" value="Save Current" onClick="saveSetup();"></td>
                           <td><input id="simsave" type="button" value="Save Last Run" onClick="if(lastRun) saveSetup(lastRun);"></td></tr>
                           <tr><td colspan="2"><input type="textbox" id="saveData"></td></tr>
                           <tr><td>Save <input id="record" type="textbox" class="thin"> Frames</td><td><input type="button" value="Generate Image" onClick="openImage()"></td></tr>
                </tr></table></td></tr><tr id="tabSel2" style="display:none"><td colspan="5">
                           <table><tr><td>Rows</td><td><input tabindex="4" class="thin" type="textbox" id="nrows"></td><td rowspan="2"><input tabindex="6" type="button" style="height:100%" value="Resize" onClick="resize(document.getElementById('nrows').value,document.getElementById('ncols').value);"></td></tr>
                                  <tr><td>Columns</td><td><input tabindex="5" class="thin" type="textbox" id="ncols"></td></tr></table></td>
                           <td><table><tr><td><input type="radio" name="resizeAnchor" value="0"></td><td><input         type="radio" name="resizeAnchor" value="1"></td><td><input type="radio" name="resizeAnchor" value="2"></td></tr>
                                      <tr><td><input type="radio" name="resizeAnchor" value="3"></td><td><input checked type="radio" name="resizeAnchor" value="4"></td><td><input type="radio" name="resizeAnchor" value="5"></td></tr>
                                      <tr><td><input type="radio" name="resizeAnchor" value="6"></td><td><input         type="radio" name="resizeAnchor" value="7"></td><td><input type="radio" name="resizeAnchor" value="8"></td></tr></table></td>
                </tr></table>
            </center>
        </td></tr></table>
        </center>
    </body>
</html>
