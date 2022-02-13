<?php
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if(!preg_match("/^PHP.*Development Server/", $_SERVER['SERVER_SOFTWARE'])){
  echo "Get outta here";
  exit;
}

const counterSuffix = '$SNcellCounter = ';
const newCellLine = '//SN NEW CELL GOES HERE';
const newCellLineText = '//SN NEW CELL TEXT GOES HERE';

$SNcellCounter = 0;
$SNcellCode = [];

//SN NEW CELL TEXT GOES HERE

if(isset($_POST['cell'])) {
    $contents = file_get_contents(__FILE__);
    $lines = explode("\n", $contents);
    $new_lines = [];

    $cellCounter = 0;
    $code = $_POST['cell'];
    foreach($lines as $line) {
        if(substr($line, 0, strlen(counterSuffix)) == counterSuffix) {
            $cellCounter = intval(substr($line, strlen(counterSuffix)));
            ++$cellCounter;
            $new_lines[] = counterSuffix . "$cellCounter;";
        }elseif($line == newCellLineText) {
            if($_POST['celltype'] == 'html') {
                $new_lines[] = '$SNcellCode[] = "";';
            }else{
                $new_lines[] = '$SNcellCode[] = <<<' . "'SNEOD'";
                $new_lines[] = $code;
                $new_lines[] = "SNEOD;";
            }
            $new_lines[] = "";
            $new_lines[] = newCellLineText;
        }elseif($line == newCellLine) {
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
            $new_lines[] = newCellLine;
        } else {
            $new_lines[] = $line;
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
    echo implode("\n", $SNcellCode);
    exit();
} else {
?>    
<html>
<head>
<title><?php echo basename(__FILE__,".php"); ?></title>
</head>
<body>
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
