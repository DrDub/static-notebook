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

$SNcellCounter = 10;
$SNcellCode = [];

$SNcellCode[] = "";

$SNcellCode[] = "";

$SNcellCode[] = <<<'SNEOD'
include __DIR__ . '/vendor/autoload.php';
SNEOD;

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
$dataset = new Rubix\ML\Datasets\Labeled($samples, $labels);
SNEOD;

$SNcellCode[] = <<<'SNEOD'
$estimator = new Rubix\ML\Classifiers\KNearestNeighbors(3);
SNEOD;

$SNcellCode[] = <<<'SNEOD'
$estimator->train($dataset);
SNEOD;

$SNcellCode[] = <<<'SNEOD'
var_dump($estimator->trained());
SNEOD;

$SNcellCode[] = <<<'SNEOD'
$samples = [
    [4, 3, 44.2],
    [2, 2, 16.7],
    [2, 4, 19.5],
    [3, 3, 55.0],
];

$dataset = new Rubix\ML\Datasets\Unlabeled($samples);

$predictions = $estimator->predict($dataset);

print_r($predictions);

SNEOD;

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
<title>Static Notebook</title>
</head>
<body>
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
<h2>Cell 3 <form method=post><input type=hidden name=src value=3><input type=submit value=Copy></form></h2>
<pre>include __DIR__ . &#039;/vendor/autoload.php&#039;;</pre>
<br>Output:<br>
<?php
include __DIR__ . '/vendor/autoload.php';
?>
<?php

?>
<h1>Training</h1>
<?php

?>
<h2>Cell 5 <form method=post><input type=hidden name=src value=5><input type=submit value=Copy></form></h2>
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
<h2>Cell 6 <form method=post><input type=hidden name=src value=6><input type=submit value=Copy></form></h2>
<pre>$dataset = new Rubix\ML\Datasets\Labeled($samples, $labels);</pre>
<br>Output:<br>
<?php
$dataset = new Rubix\ML\Datasets\Labeled($samples, $labels);
?>
<?php

?>
<h2>Cell 7 <form method=post><input type=hidden name=src value=7><input type=submit value=Copy></form></h2>
<pre>$estimator = new Rubix\ML\Classifiers\KNearestNeighbors(3);</pre>
<br>Output:<br>
<?php
$estimator = new Rubix\ML\Classifiers\KNearestNeighbors(3);
?>
<?php

?>
<h2>Cell 8 <form method=post><input type=hidden name=src value=8><input type=submit value=Copy></form></h2>
<pre>$estimator-&gt;train($dataset);</pre>
<br>Output:<br>
<?php
$estimator->train($dataset);
?>
<?php

?>
<h2>Cell 9 <form method=post><input type=hidden name=src value=9><input type=submit value=Copy></form></h2>
<pre>var_dump($estimator-&gt;trained());</pre>
<br>Output:<br>
<?php
var_dump($estimator->trained());
?>
<?php

?>
<h2>Cell 10 <form method=post><input type=hidden name=src value=10><input type=submit value=Copy></form></h2>
<pre>$samples = [
    [4, 3, 44.2],
    [2, 2, 16.7],
    [2, 4, 19.5],
    [3, 3, 55.0],
];

$dataset = new Rubix\ML\Datasets\Unlabeled($samples);

$predictions = $estimator-&gt;predict($dataset);

print_r($predictions);
</pre>
<br>Output:<br>
<?php
$samples = [
    [4, 3, 44.2],
    [2, 2, 16.7],
    [2, 4, 19.5],
    [3, 3, 55.0],
];

$dataset = new Rubix\ML\Datasets\Unlabeled($samples);

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
