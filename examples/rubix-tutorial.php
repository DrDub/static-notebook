<?php

//SN header starts
include __DIR__ . '/vendor/autoload.php';

use Rubix\ML\Datasets\Labeled;
use Rubix\ML\Datasets\Unlabeled;
use Rubix\ML\Classifiers\KNearestNeighbors;
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

$SNcellCounter = 11;
$SNcellCode = [];

$SNcellCode[] = "";

$SNcellCode[] = "";

$SNcellCode[] = "";

$SNcellCode[] = <<<'SNEOD'
$samples = [
    [3, 4, 50.5],
    [1, 5, 24.7],
    [4, 4, 62.0],
    [3, 2, 31.1],
];

$labels = ['married', 'divorced', 'married', 'divorced'];
SNEOD;

$SNcellCode[] = <<<'SNEOD'
$dataset = new Labeled($samples, $labels);
SNEOD;

$SNcellCode[] = <<<'SNEOD'
$estimator = new KNearestNeighbors(3);
SNEOD;

$SNcellCode[] = <<<'SNEOD'
$estimator->train($dataset);
SNEOD;

$SNcellCode[] = <<<'SNEOD'
var_dump($estimator->trained());
SNEOD;

$SNcellCode[] = "";

$SNcellCode[] = <<<'SNEOD'
$samples = [
    [4, 3, 44.2],
    [2, 2, 16.7],
    [2, 4, 19.5],
    [3, 3, 55.0],
];
SNEOD;

$SNcellCode[] = <<<'SNEOD'
$dataset = new Unlabeled($samples);

$predictions = $estimator->predict($dataset);

print_r($predictions);
SNEOD;

//SN NEW CELL TEXT GOES HERE

$SNcontents = file_get_contents(__FILE__);
$SNlines = explode("\n", $SNcontents);

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
    $SNinHeader = false;
    foreach($SNlines as $line) {
        if($line == SNheaderStarts) {
            $SNinHeader = true;
        }elseif($line == SNheaderEnds) {
            $SNinHeader = false;
        }elseif($SNinHeader) {
            echo htmlspecialchars($line). '<br/>'."\n";
        }
    }
?>
<form method="post">
<textarea name="header" rows="5" cols="120">
</textarea><br/>
<input type="submit" value="Add">
</form>
<?php
    
    
?>
<p><b>Note</b></p>
<p>This code from <a href="https://docs.rubixml.com/1.0/basic-introduction.html">https://docs.rubixml.com/1.0/basic-introduction.html</a></p>

<?php

?>
<p>RubixML already installed in system through:</p>
<pre>
composer require rubix/ml
</pre>
<?php

?>
<h1>Training</h1>
<?php

?>
<h2>Cell 4 <form method=post><input type=hidden name=src value=4><input type=submit value=Copy></form></h2>
<pre>$samples = [
    [3, 4, 50.5],
    [1, 5, 24.7],
    [4, 4, 62.0],
    [3, 2, 31.1],
];

$labels = [&#039;married&#039;, &#039;divorced&#039;, &#039;married&#039;, &#039;divorced&#039;];</pre>
<br>Output:<br>
<?php
$samples = [
    [3, 4, 50.5],
    [1, 5, 24.7],
    [4, 4, 62.0],
    [3, 2, 31.1],
];

$labels = ['married', 'divorced', 'married', 'divorced'];
?>
<?php

?>
<h2>Cell 5 <form method=post><input type=hidden name=src value=5><input type=submit value=Copy></form></h2>
<pre>$dataset = new Labeled($samples, $labels);</pre>
<br>Output:<br>
<?php
$dataset = new Labeled($samples, $labels);
?>
<?php

?>
<h2>Cell 6 <form method=post><input type=hidden name=src value=6><input type=submit value=Copy></form></h2>
<pre>$estimator = new KNearestNeighbors(3);</pre>
<br>Output:<br>
<?php
$estimator = new KNearestNeighbors(3);
?>
<?php

?>
<h2>Cell 7 <form method=post><input type=hidden name=src value=7><input type=submit value=Copy></form></h2>
<pre>$estimator-&gt;train($dataset);</pre>
<br>Output:<br>
<?php
$estimator->train($dataset);
?>
<?php

?>
<h2>Cell 8 <form method=post><input type=hidden name=src value=8><input type=submit value=Copy></form></h2>
<pre>var_dump($estimator-&gt;trained());</pre>
<br>Output:<br>
<?php
var_dump($estimator->trained());
?>
<?php

?>
<h1>Executing</h1>
<?php

?>
<h2>Cell 10 <form method=post><input type=hidden name=src value=10><input type=submit value=Copy></form></h2>
<pre>$samples = [
    [4, 3, 44.2],
    [2, 2, 16.7],
    [2, 4, 19.5],
    [3, 3, 55.0],
];</pre>
<br>Output:<br>
<?php
$samples = [
    [4, 3, 44.2],
    [2, 2, 16.7],
    [2, 4, 19.5],
    [3, 3, 55.0],
];
?>
<?php

?>
<h2>Cell 11 <form method=post><input type=hidden name=src value=11><input type=submit value=Copy></form></h2>
<pre>$dataset = new Unlabeled($samples);

$predictions = $estimator-&gt;predict($dataset);

print_r($predictions);</pre>
<br>Output:<br>
<?php
$dataset = new Unlabeled($samples);

$predictions = $estimator->predict($dataset);

print_r($predictions);
?>
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
