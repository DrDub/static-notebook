<?php

//SN header starts
//SN header ends

header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if(!preg_match("/^PHP.*Development Server/", $_SERVER['SERVER_SOFTWARE'])){
  echo "Get outta here";
  exit;
}

const SNcounterSuffix = '$SNcellCounter = ';
const SNnewCellLine = '//SN NEW CELL GOES HERE';
const SNnewCellLineText = '//SN NEW CELL TEXT GOES HERE';
const SNheaderStarts = '//SN header starts';
const SNheaderEnds = '//SN header ends';

$SNcellCounter = 0;
$SNcellCode = [];

//SN NEW CELL TEXT GOES HERE

$SNcontents = file_get_contents(__FILE__);
$SNlines = explode("\n", $SNcontents);
$SNheader = [];

$SNinHeader = false;
foreach($SNlines as $line) {
    if($line == SNheaderStarts) {
        $SNinHeader = true;
    }elseif($line == SNheaderEnds) {
        $SNinHeader = false;
    }elseif($SNinHeader) {
        $SNheader[] = $line;
    }
}


if(isset($_POST['header']) || isset($_POST['cell'])) {
    $new_lines = [];
    if(isset($_POST['header'])) {
        $code = $_POST['header'];
        foreach($SNlines as $line) {
            if($line == SNheaderEnds) {
                $new_lines[] = $code;
            }
            $new_lines[] = $line;
        }
    }else{
        $cellCounter = 0;
        $code = $_POST['cell'];
        foreach($SNlines as $line) {
            if(substr($line, 0, strlen(SNcounterSuffix)) == SNcounterSuffix) {
                $cellCounter = intval(substr($line, strlen(SNcounterSuffix)));
                ++$cellCounter;
                $new_lines[] = SNcounterSuffix . "$cellCounter;";
            }elseif($line == SNnewCellLineText) {
                if($_POST['celltype'] == 'html') {
                    $new_lines[] = '$SNcellCode[] = "";';
                }else{
                    $new_lines[] = '$SNcellCode[] = <<<' . "'SNEOD'";
                    $new_lines[] = $code;
                    $new_lines[] = "SNEOD;";
                }
                $new_lines[] = "";
                $new_lines[] = SNnewCellLineText;
            }elseif($line == SNnewCellLine) {
                $new_lines[] = '?>';
                if($_POST['celltype'] == 'html') {
                    $new_lines[] = $code;
                }else{
                    $new_lines[] = "<h2>Cell $cellCounter <form method=post><input type=hidden name=src value=$cellCounter><input type=submit value=Copy></form></h2>";
                    $new_lines[] = "<pre>".htmlspecialchars($code)."</pre>";
                    $new_lines[] = "<br>Output:<br>";
                    $new_lines[] = "<?php";
                    $new_lines[] = $code;
                    $new_lines[] = '?>';
                }
                $new_lines[] = '<?php';
                $new_lines[] = '';
                $new_lines[] = SNnewCellLine;
            } else {
                $new_lines[] = $line;
            }
        }
    }

    file_put_contents(__FILE__, implode("\n", $new_lines), LOCK_EX);
    sleep(1);
    header("refresh:2;url=http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");
    echo "Executing...";
    exit();
} elseif (isset($_POST['download'])) {
    header('Content-type: text/plain');
    header('Content-Disposition: attachment; filename="'.basename(__FILE__,".php").'.txt"');
    echo '<?php'."\n";
    echo implode("\n", $SNheader);
    echo "\n\n";
    echo implode("\n", $SNcellCode);
    exit();
} else {
?>    
<html>
<head>
<title><?php echo basename(__FILE__,".php"); ?></title>
</head>
<body>
<h2>Header</h2>
<?php
    foreach($SNheader as $line) {
        echo htmlspecialchars($line). '<br/>'."\n";
    }
?>
<form method="post">
<textarea name="header" rows="5" cols="120">
</textarea><br/>
<input type="submit" value="Add">
</form>
<?php
    
    
//SN NEW CELL GOES HERE

?>
<h2 id="newcell">New Cell</h2>
<form method="post">
<textarea name="cell" rows="10" cols="120">
<?php
if(isset($_POST['src'])) {
    echo htmlspecialchars($SNcellCode[intval($_POST['src']) - 1]);
}
?>
</textarea>
<p>
  <label for="celltype">Cell type:</label>
    <select name="celltype" id="celltype">
      <option value="php">PHP</option>
      <option value="html">HTML</option>
    </select>
    <input type="submit" value="Execute">
</p>
</form>
<form method="post">
<input type="hidden" name="download">
<input type="submit" value="Download">
</form>
<script>
document.getElementById("newcell").scrollIntoView(true);
</script>
</body>
</html>
<?php
}
?>
