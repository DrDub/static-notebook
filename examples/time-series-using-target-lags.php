<?php

// Static Notebook Version 0.1

// Derived from https://github.com/DrDub/static-notebook

//SN header starts
include __DIR__ . '/vendor/autoload.php';

use Gregwar\GnuPlot\GnuPlot;
use Rubix\ML\Classifiers\SVR;
use Rubix\ML\Kernels\SVM\RBF;
use Rubix\ML\Datasets\Labeled;
use Rubix\ML\Datasets\Unlabeled;
//SN header ends

const SN_COPY_STARTS      = '//SN copyright starts';
const SN_COPY_ENDS        = '//SN copyright ends';
const SN_HEADER_STARTS    = '//SN header starts';
const SN_HEADER_ENDS      = '//SN header ends';
const SN_NEW_CELL_LINE    = '//SN NEW CELL GOES HERE';
const SN_CELL_PHP_STARTS  = '//SN cell PHP starts';
const SN_CELL_PHP_ENDS    = '//SN cell PHP ends';
const SN_CELL_HTML_STARTS = '//SN cell HTML starts';
const SN_CELL_HTML_ENDS   = '//SN cell HTML ends';
const SN_PHP_CELL  = "php";
const SN_HTML_CELL = "html";
const SN_BASED = "// Derived from https://github.com/DrDub/static-notebook";

//TODO catch syntax errors with eval("if(false){...}")
const SN_CELL_PHP_BEFORE = <<<'SNEOD'
$SNcode = htmlspecialchars($SNcells[$SNcellCounter][1]);
++$SNcellCounter;
?>
<h2>Cell <?php echo $SNcellCounter; ?></h2>               
    <input type=button value=Copy onclick='document.getElementById("cell").value="<?php echo $SNcode; ?>"; return false;'>
<pre><?php echo $SNcode; ?></pre>
<br>Output:<br>
<?php
ob_start();
$SNexc = null;
try{
SNEOD;

const SN_CELL_PHP_AFTER = <<<'SNEOD'
}catch(Throwable $e){
   $SNexc = $e;
}
$SNcells[$SNcellCounter - 1][] = ob_get_contents();
ob_end_clean();
$SNcells[$SNcellCounter - 1][] = $SNexc;
if(!$SNcli){
  if($SNexc) {
    echo '<font color="red">';
  }
  echo $SNcells[$SNcellCounter - 1][2];
  if($SNexc) {
    echo '<br>';
    echo $SNexc->getTraceAsString();
    echo '</font>';
  }
}
SNEOD;

const SN_CELL_HTML_BEFORE = <<<'SNEOD'
++$SNcellCounter;
?>
SNEOD;

const SN_CELL_HTML_AFTER = <<<'SNEOD'
<?php
SNEOD;

$SNcellPhpBeforeLen  = count(explode("\n", SN_CELL_PHP_BEFORE));
$SNcellPhpAfterLen   = count(explode("\n", SN_CELL_PHP_AFTER));
$SNcellHtmlBeforeLen = count(explode("\n", SN_CELL_HTML_BEFORE));
$SNcellHtmlAfterLen  = count(explode("\n", SN_CELL_HTML_AFTER));

// parse cells from source file (this file)

$SNcellCounter = 0;

$SNcontents = file_get_contents(__FILE__);
$SNlines = explode("\n", $SNcontents);
$SNheader = [];
$SNcells  = [];
    
$SNinHeader = false;
$SNinPhpCell = false;
$SNinHtmlCell = false;
foreach($SNlines as $line) {
    if($line == SN_HEADER_STARTS) {
        $SNinHeader = true;
    }elseif($line == SN_HEADER_ENDS) {
        $SNinHeader = false;
    }elseif($line == SN_CELL_PHP_STARTS) {
        $SNinPhpCell = true;
        $SNcells[] = array(SN_PHP_CELL, []);
        ++$SNcellCounter;
    }elseif($line == SN_CELL_PHP_ENDS) {
        $SNcells[$SNcellCounter - 1][1] = implode("\n", array_slice($SNcells[$SNcellCounter - 1][1], $SNcellPhpBeforeLen, -$SNcellPhpAfterLen));
        $SNinPhpCell = false;
    }elseif($line == SN_CELL_HTML_STARTS) {
        $SNinHtmlCell = true;
        $SNcells[] = array(SN_HTML_CELL, []);  
        ++$SNcellCounter;
    }elseif($line == SN_CELL_HTML_ENDS) {
        $SNcells[$SNcellCounter - 1][1] = implode("\n", array_slice($SNcells[$SNcellCounter - 1][1], $SNcellHtmlBeforeLen, -$SNcellHtmlAfterLen));
        $SNinHtmlCell = false;
    }elseif($SNinHeader) {
        $SNheader[] = $line;
    }elseif($SNinPhpCell || $SNinHtmlCell) {
        $SNcells[$SNcellCounter - 1][1][] = $line;
    }
}

$SNcli = false;
if(getenv('TERM') && PHP_SAPI === 'cli') {
    // running command line
    $SNoptions = getopt("u:hx:dp");
    if(count($SNoptions) == 0 or isset($SNoptions['h'])) {
        echo 'Usage: ' . $argv[0] . " <options>\n";
        echo "Options:\n";
        echo "\t -h Show this message\n";
        echo "\t -u <url> Upload cells to another notebook\n";
        echo "\t -x <file.ipynb> Export as ipynb\n";
        echo "\t -d Execute and dump the resulting HTML\n";
        echo "\t -p Dump as php\n";
    }
    if(isset($SNoptions['p'])) {
        echo '<?php'."\n";
        foreach($SNheader as $header) {
            echo $header . "\n";
        }
        foreach($SNcells as $cell) {
            if($cell[0] == SN_PHP_CELL) {
                echo $cell[1] . "\n\n";
            }
        }
    }
    if(isset($SNoptions['u'])) {
        // need curl installed
        $header = implode("\n", $SNheader);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $SNoptions['u']);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, [ 'header' => $header ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        if(! $response) {
            echo "Error contacting server:\n" . $response;
            exit;
        }
        echo "posted headers\n";
        sleep(2);
        $cellCounter = 0;
        foreach($SNcells as $cell) {
            ++$cellCounter;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $SNoptions['u']);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, [ 'celltype' => $cell[0], 'cell' => $cell[1] ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            curl_close($ch);
            if(! $response) {
                echo "Error contacting server:\n" . $response;
                exit;
            }
            echo "posted cell $cellCounter\n";
            sleep(2);
        }
                    
    }
    if(! isset($SNoptions['d']) && !isset($SNoptions['x'])){
        exit;
    }
    $SNcli = true;
}

// render!

if(! $SNcli) {
    header("Cache-Control: no-store, no-cache, must-revalidate");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");

    if(!preg_match("/^PHP.*Development Server/", $_SERVER['SERVER_SOFTWARE'])){
        echo "Get outta here";
        exit;
    }
}

if(isset($_POST['header']) || isset($_POST['cell'])) { //modify source
    $new_lines = [];
    $in_copy = false;
    if(isset($_POST['header'])) {
        $code = $_POST['header'];
        $code = str_replace("\r", "", $code);
        foreach($SNlines as $line) {
            if($line == SN_COPY_STARTS) {
                $in_copy = true;
            }
            if($in_copy) {
                if($line == SN_COPY_ENDS) {
                    $new_lines[] = SN_BASED;
                    $in_copy = false;
                }
            } else {
                if($line == SN_HEADER_ENDS) {
                    $new_lines[] = $code;
                }
                $new_lines[] = $line;
            }
        }
    }else{
        $cellCounter = 0;
        $code = $_POST['cell'];
        $code = str_replace("\r", "", $code);
        foreach($SNlines as $line) {
            if($line == SN_COPY_STARTS) {
                $in_copy = true;
            }
            if($in_copy) {
                if($line == SN_COPY_ENDS) {
                    $new_lines[] = SN_BASED;
                    $in_copy = false;
                }
            } else {
                if($line == SN_NEW_CELL_LINE) {
                    if($_POST['celltype'] == SN_HTML_CELL) {
                        $new_lines[] = SN_CELL_HTML_STARTS;
                        $new_lines[] = SN_CELL_HTML_BEFORE;
                        $new_lines[] = $code;
                        $new_lines[] = SN_CELL_HTML_AFTER;
                        $new_lines[] = SN_CELL_HTML_ENDS;
                    }else{
                        $new_lines[] = SN_CELL_PHP_STARTS;
                        $new_lines[] = SN_CELL_PHP_BEFORE;
                        $new_lines[] = $code;
                        $new_lines[] = SN_CELL_PHP_AFTER;
                        $new_lines[] = SN_CELL_PHP_ENDS;
                    }
                    $new_lines[] = '';
                    $new_lines[] = SN_NEW_CELL_LINE;
                } else {
                    $new_lines[] = $line;
                }
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
    foreach($SNcells as $cell) {
        if($cell[0] == SN_PHP_CELL) {
            echo implode("\n", $cell[1]);
        }
    }
    exit();
} else { // nothing POSTed
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
$SNcellCounter = 0; // reset counter for execution

//SN cell HTML starts
++$SNcellCounter;
?>
<p>
This is a (partial) PHP adaptation of this <a href="https://github.com/DrDub/riiaa20_ws25_feateng_space_time/blob/master/notebooks/2_Temporal_Time_Series-from-workshop.ipynb">Python notebook presented at a workshop in RIIAA'20</a>. The notebook in turn is adapted from Chapter 7 of <a href="http://artoffeatureengineering.com/">The Art of Feature Engineering</a>. 
</p>
<p>
This code distributed under MIT license.
</p>
<?php
//SN cell HTML ends

//SN cell HTML starts
++$SNcellCounter;
?>
<h1>Handling Time Series using Target Lags</h1>

<p>This is a small datasets for country-level population prediction based on historical data.</p>
<p>We want to predict the population of a given country in a given year. Note that there are very few countries in the world, as such we will use few features to avoid having many more parameters than training data.</p>
<p>In fact, besides the population data, we will only use two other features: the number of in links and out links for the country Wikipedia page. The idea being that larger countries might have larger Wikipedia pages (resulting in more out links) and have more Wikipedia related to them (resulting in a larger number of in links).</p>
<p>Let us start by identifying all entities of type <a href="http://dbpedia.org/ontology/Country">http://dbpedia.org/ontology/Country</a> (Cell #18).</p>
<?php
//SN cell HTML ends

//SN cell PHP starts
$SNcode = htmlspecialchars($SNcells[$SNcellCounter][1]);
++$SNcellCounter;
?>
<h2>Cell <?php echo $SNcellCounter; ?></h2>               
    <input type=button value=Copy onclick='document.getElementById("cell").value="<?php echo $SNcode; ?>"; return false;'>
<pre><?php echo $SNcode; ?></pre>
<br>Output:<br>
<?php
ob_start();
$SNexc = null;
try{
// from CELL 23
$rels = array();
$tsv = explode("\n", file_get_contents("ch7_cell22_rels_in_out.tsv"));
array_pop($tsv); // last
array_shift($tsv); // header
foreach($tsv as $line) {
   $parts = explode("\t", $line);
   $country = $parts[0];
   $rels_out = $parts[1];
   $rels_in = $parts[2];
   $rels[$country] = [ intval($rels_out), intval($rels_in) ];
}

$mapping = array();
$m = explode("\n", file_get_contents("ch7_cell21_wb_uri_mapping.tsv"));
array_pop($m); // last
foreach($m as $line) {
  $parts = explode("\t", $line);
  $wb = $parts[0];
  $db = $parts[1];
  //echo "$wb ". htmlspecialchars($db). "<br>";
  $mapping[$wb] = $db;
}

$data = [];
$wb = explode("\n", file_get_contents("ch7_cell19_wb_pop.tsv"));
echo "wb_pop has " . count($wb) . " lines<br>";
$line = array_shift($wb); // header
$header = explode("\t", $line);
array_splice($header, 1, 0, ["rels_out", "rels_in"]);
foreach($wb as $line) {
  $fields = explode("\t", $line);
  $wb_country = array_shift($fields);
  if(! isset($mapping[$wb_country])) {
     continue;
  }
  while($fields[count($fields)-1] == '') {
     array_pop($fields);
  }
  $db_country = $mapping[$wb_country];
  $tuple = [];
  $tuple[] = $rels[$db_country][0];
  $tuple[] = $rels[$db_country][1];
  foreach($fields as $field) { 
     if($field) {
        $tuple[] = intval($field);
     }else{
        $tuple[] = 0;
     }
  }
  $data[] = [ $wb_country, $tuple ];
}
echo "Data for " . count($data) . " countries\n";
}catch(Throwable $e){
   $SNexc = $e;
}
$SNcells[$SNcellCounter - 1][] = ob_get_contents();
ob_end_clean();
$SNcells[$SNcellCounter - 1][] = $SNexc;
if(!$SNcli){
  if($SNexc) {
    echo '<font color="red">';
  }
  echo $SNcells[$SNcellCounter - 1][2];
  if($SNexc) {
    echo '<br>';
    echo $SNexc->getTraceAsString();
    echo '</font>';
  }
}
//SN cell PHP ends

//SN cell PHP starts
$SNcode = htmlspecialchars($SNcells[$SNcellCounter][1]);
++$SNcellCounter;
?>
<h2>Cell <?php echo $SNcellCounter; ?></h2>               
    <input type=button value=Copy onclick='document.getElementById("cell").value="<?php echo $SNcode; ?>"; return false;'>
<pre><?php echo $SNcode; ?></pre>
<br>Output:<br>
<?php
ob_start();
$SNexc = null;
try{
// save all data
$tsv = [];
$tsv[] = implode("\t", $header);
foreach($data as $pair) {
  $line = $pair[0];
  foreach($pair[1] as $point) {
     $line .= "\t" . $point;
  }
  $tsv[] = $line;
}
file_put_contents("cell23_countries_data.tsv", implode("\n", $tsv)."\n");

// split train and final test
mt_srand(42);

// Fisher-Yates shuffle as PHP shuffle is self-seeded
for ($i = count($data) - 1; $i > 0; $i--) {
  $j = mt_rand(0, $i);
  $tmp = $data[$i];
  $data[$i] = $data[$j];
  $data[$j] = $tmp;
}
$pivot = intval(count($data) * 0.8);
$devset = array_slice($data, 0, $pivot);
$heldout = array_slice($data, $pivot);
$tsv = [];
$tsv[] = implode("\t", $header);
foreach($devset as $pair) {
  $line = $pair[0];
  foreach($pair[1] as $point) {
     $line .= "\t" . $point;
  }
  $tsv[] = $line;
}
file_put_contents("cell23_countries_dev.tsv", implode("\n", $tsv)."\n");

$tsv = [];
$tsv[] = implode("\t", $header);
foreach($heldout as $pair) {
  $line = $pair[0];
  foreach($pair[1] as $point) {
     $line .= "\t" . $point;
  }
  $tsv[] = $line;
}
file_put_contents("cell23_countries_held.tsv", implode("\n", $tsv)."\n");

echo "<br>Devset size:  " . count($devset);
echo "<br>Heldout size: " . count($heldout);

}catch(Throwable $e){
   $SNexc = $e;
}
$SNcells[$SNcellCounter - 1][] = ob_get_contents();
ob_end_clean();
$SNcells[$SNcellCounter - 1][] = $SNexc;
if(!$SNcli){
  if($SNexc) {
    echo '<font color="red">';
  }
  echo $SNcells[$SNcellCounter - 1][2];
  if($SNexc) {
    echo '<br>';
    echo $SNexc->getTraceAsString();
    echo '</font>';
  }
}
//SN cell PHP ends

//SN cell HTML starts
++$SNcellCounter;
?>
<p>With the data split properly, we can start some EDA on it.</p>

<h2>EDA</h2>

<p>Let's plot the relations and current population to see if there is a correlation (Cell #24).</p>

<?php
//SN cell HTML ends

//SN cell PHP starts
$SNcode = htmlspecialchars($SNcells[$SNcellCounter][1]);
++$SNcellCounter;
?>
<h2>Cell <?php echo $SNcellCounter; ?></h2>               
    <input type=button value=Copy onclick='document.getElementById("cell").value="<?php echo $SNcode; ?>"; return false;'>
<pre><?php echo $SNcode; ?></pre>
<br>Output:<br>
<?php
ob_start();
$SNexc = null;
try{
// from CELL 24

$pops = [];
$num_in_rels = [];
$num_out_rels = [];
foreach($devset as $pair) {
  $fields = $pair[1];
  $pop = floatval($fields[count($fields)- 1]);
  $out_rels = floatval($fields[1]);
  $in_rels = floatval($fields[2]);
  if($out_rels == 0){
     continue;
  }
  $pops[] = log($pop, 10);
  $num_out_rels[] = log($out_rels, 10);
  $num_in_rels[] = log($in_rels, 10);
}

}catch(Throwable $e){
   $SNexc = $e;
}
$SNcells[$SNcellCounter - 1][] = ob_get_contents();
ob_end_clean();
$SNcells[$SNcellCounter - 1][] = $SNexc;
if(!$SNcli){
  if($SNexc) {
    echo '<font color="red">';
  }
  echo $SNcells[$SNcellCounter - 1][2];
  if($SNexc) {
    echo '<br>';
    echo $SNexc->getTraceAsString();
    echo '</font>';
  }
}
//SN cell PHP ends

//SN cell PHP starts
$SNcode = htmlspecialchars($SNcells[$SNcellCounter][1]);
++$SNcellCounter;
?>
<h2>Cell <?php echo $SNcellCounter; ?></h2>               
    <input type=button value=Copy onclick='document.getElementById("cell").value="<?php echo $SNcode; ?>"; return false;'>
<pre><?php echo $SNcode; ?></pre>
<br>Output:<br>
<?php
ob_start();
$SNexc = null;
try{
class MyPlot extends GnuPlot {
    public function __construct() {
        parent::__construct();
        $this->mode = 'points';
    }
    protected function sendCommand($command) {
        //echo "$command\n<br>\n";
        parent::sendCommand($command);
    }
    public function get($format = self::TERMINAL_PNG) {
        $this->sendInit();
        $this->sendCommand("set terminal $format size {$this->width}{$this->unit}, {$this->height}{$this->unit}");
        fflush($this->stdout);
        $this->plot();

        // Reading data, timeout=500ms
        $result = '';
        $timeout = 500;
        do {
            stream_set_blocking($this->stdout, false);
            $data = fread($this->stdout, 128);
            $result .= $data;
            usleep(10000);
            $timeout -= 5;
        } while ($timeout > 0 || $data);

        return $result;
    }
}
$plot = new MyPlot;

$plot
  ->setGraphTitle('Log pop vs. log in/out rels')
  ->setXLabel('scaled log population')
  ->setYLabel('scaled log number of relations')
  ->setWidth(500)
  ->setHeight(300);
$plot->setTitle(0, 'in');
for($idx=0; $idx<count($pops); $idx++) {
  $plot->push($pops[$idx], $num_in_rels[$idx]);
}
$plot->setTitle(1, 'out');
for($idx=0; $idx<count($pops); $idx++) {
  $plot->push($pops[$idx], $num_out_rels[$idx], 1);
}
//$plot->display();
$png = $plot->get();
$base64 = 'data:image/png;base64,' . base64_encode($png);

echo "<img src='$base64'/>";


}catch(Throwable $e){
   $SNexc = $e;
}
$SNcells[$SNcellCounter - 1][] = ob_get_contents();
ob_end_clean();
$SNcells[$SNcellCounter - 1][] = $SNexc;
if(!$SNcli){
  if($SNexc) {
    echo '<font color="red">';
  }
  echo $SNcells[$SNcellCounter - 1][2];
  if($SNexc) {
    echo '<br>';
    echo $SNexc->getTraceAsString();
    echo '</font>';
  }
}
//SN cell PHP ends

//SN cell HTML starts
++$SNcellCounter;
?>
<p>From the figure we can see that the number of in relations is informative, but the number of out relations is not, most of the countries are involved in the same number of standard relations.</p>

<p>Let us now take 10 random countries and look at their time series data (Cell #25).</p>
<?php
//SN cell HTML ends

//SN cell PHP starts
$SNcode = htmlspecialchars($SNcells[$SNcellCounter][1]);
++$SNcellCounter;
?>
<h2>Cell <?php echo $SNcellCounter; ?></h2>               
    <input type=button value=Copy onclick='document.getElementById("cell").value="<?php echo $SNcode; ?>"; return false;'>
<pre><?php echo $SNcode; ?></pre>
<br>Output:<br>
<?php
ob_start();
$SNexc = null;
try{
// from CELL 25

$indices = [];
for($idx=0; $idx<count($devset); $idx++){
  $indices[]=$idx;
}
mt_srand(42);
for ($i = count($indices) - 1; $i > 0; $i--) {
  $j = mt_rand(0, $i);
  $tmp = $indices[$i];
  $indices[$i] = $indices[$j];
  $indices[$j] = $tmp;
}
$to_show=[];

$years=[];
for($idx=3;$idx<count($header); $idx++){
  $years[]= intval($header[$idx]);
}

echo '<table>';

for($idx=0;$idx<12; $idx++){
  $pair=$devset[$indices[$idx]];
  if($idx % 2 == 0) { 
    echo '<tr>';
  }
  $plot=new MyPlot;
  $plot
    ->setGraphTitle($pair[0])
    ->setXLabel('year')
    ->setYLabel('population')
    ->setWidth(400)
    ->setHeight(200)
    ->setTitle(0, "");
   for($idx2=2; $idx2<count($pair[1]); $idx2++) {
     $plot->push($years[$idx2-2], $pair[1][$idx2]);
   }
   $png = $plot->get();
   $base64 = 'data:image/png;base64,' . base64_encode($png);

   echo "<td><img src='$base64'/></td>";
   if($idx % 2 == 1) {
      echo '</tr>'."\n";
   }
}
echo '</table>'."\n";



}catch(Throwable $e){
   $SNexc = $e;
}
$SNcells[$SNcellCounter - 1][] = ob_get_contents();
ob_end_clean();
$SNcells[$SNcellCounter - 1][] = $SNexc;
if(!$SNcli){
  if($SNexc) {
    echo '<font color="red">';
  }
  echo $SNcells[$SNcellCounter - 1][2];
  if($SNexc) {
    echo '<br>';
    echo $SNexc->getTraceAsString();
    echo '</font>';
  }
}
//SN cell PHP ends

//SN cell HTML starts
++$SNcellCounter;
?>
<p>In the figure we can see trend reversal, missing data (Sint Maarten) and a variety of curves.
</p>
<?php
//SN cell HTML ends

//SN cell HTML starts
++$SNcellCounter;
?>
<h1>No TS data</h1>
<p>
Let us start by using only the number of relations (Cell #33) and the year.
</p>
<?php
//SN cell HTML ends

//SN NEW CELL GOES HERE

if($SNcli) {
    if(isset($SNoptions['x'])) {
        $cells = [];
        $execCounter = 0;
        $idCounter = 0;

        if(count($SNheader) > 0){
            ++$idCounter;
            $source = [];
            foreach($SNheader as $line){
                $source[] = $line . "\n";
            }
            $cells[] = array(
                "cell_type" => "code",
                "execution_count" => $execCounter,
                "id" => "nbid-" . $idCounter,
                "metadata" => new stdClass,
                "outputs" => [],
                "source" => $source
            );
        }
        foreach($SNcells as $cell) {
            ++$idCounter;
            $lines = explode("\n", $cell[1]);
            $source = [];
            foreach($lines as $line){
                $source[] = $line . "\n";
            }
            if($cell[0] == SN_PHP_CELL) {
                ++$execCounter;
                $outputs=[];
                $outputs[] = array(
                    "data" => array( "text/html" => $cell[2] ),
                    "execution_count" => $execCounter,
                    "metadata" => new stdClass,
                    "output_type" => "execute_result"
                );
                if($cell[3]) {
                    $outputs[] = array(
                        "data" => array(
                            "text/html" => '<font color="red">' .
                            implode("<br>", explode("\n", $cell[3]->getTraceAsString())) . '</font>'),
                        "execution_count" => $execCounter,
                        "metadata" => new stdClass,
                        "output_type" => "execute_result"
                    );
                }
                $cells[] = array(
                    "cell_type" => "code",
                    "execution_count" => $execCounter,
                    "id" => "nbid-" . $idCounter,
                    "metadata" => new stdClass,
                    "outputs" => $outputs,
                    "source" => $source
                );
            }elseif($cell[0] == SN_HTML_CELL) {
                array_unshift($source, "<div>\n");
                $source[] = "</div>\n";
                $cells[] = array(
                    "cell_type" => "markdown",
                    "id" => "nbid-" . $idCounter,
                    "metadata" => new stdClass,
                    "source" => $source
                );
            }
        }
        // empty cell at the end, always
        $cells[] = array(
            "cell_type" => "code",
            "execution_count" => NULL,
            "id" => "nbid-" . (++$idCounter),
            "metadata" => new stdClass,
            "outputs" => [],
            "source" => []
        );
        
        $metadata = array(
            "kernelspec" => array(
                "display_name" => "PHP",
                "language" => "php",
                "name" => "jupyther-php"
            ),
            "language_info" => array(
                "file_extension" => ".php",
                "mimetype" => "text/x-php",
                "name" => "PHP",
                "pygments_lexer" => "PHP",
                "version" => "7.4.25"
            ));
        $nb = array(
            "cells" => $cells,
            "metadata" => $metadata,
            "nbformat" => 4,
            "nbformat_minor" => 5
            
        );
        file_put_contents($SNoptions["x"], json_encode($nb));
        echo "\n\nEXPORTED TO " . $SNoptions["x"] . "\n";
    }
}else{
?>
<h2 id="newcell">New Cell</h2>
<form method="post">
<textarea id="cell" name="cell" rows="10" cols="120">
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
<?php
} // no CLI
?>
</body>
</html>
<?php
} // nothing POSTed
?>
