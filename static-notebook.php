<?php

// Static Notebook Version 0.1

//SN copyright starts
/*
static-notebook.php, Copyright (C) 2022 Pablo Duboue
Distributed under the terms of the MIT License.
*/
//SN copyright ends

//SN header starts
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
if(!$SNcli or isset($SNoptions['d'])){
  if($SNexc) {
    echo '<font color="red">';
  }
  echo $SNcells[$SNcellCounter - 1][2];
  if($SNexc) {
    echo '<br>';
    echo SN_jTraceEx($SNexc);
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

function SN_jTraceEx($e, $seen=null) {
    $starter = $seen ? 'Caused by: ' : '';
    $result = array();
    if (!$seen) $seen = array();
    $trace  = $e->getTrace();
    $prev   = $e->getPrevious();
    $result[] = sprintf('%s%s: %s', $starter, get_class($e), $e->getMessage());
    $file = $e->getFile();
    $line = $e->getLine();
    while (true) {
        $current = "$file:$line";
        if (is_array($seen) && in_array($current, $seen)) {
            $result[] = sprintf(' ... %d more', count($trace)+1);
            break;
        }
        $result[] = sprintf(' at %s%s%s(%s%s%s)',
                                    count($trace) && array_key_exists('class', $trace[0]) ? str_replace('\\', '.', $trace[0]['class']) : '',
                                    count($trace) && array_key_exists('class', $trace[0]) && array_key_exists('function', $trace[0]) ? '.' : '',
                                    count($trace) && array_key_exists('function', $trace[0]) ? str_replace('\\', '.', $trace[0]['function']) : '(main)',
                                    $line === null ? $file : basename($file),
                                    $line === null ? '' : ':',
                                    $line === null ? '' : $line);
        if (is_array($seen))
            $seen[] = "$file:$line";
        if (!count($trace))
            break;
        $file = array_key_exists('file', $trace[0]) ? $trace[0]['file'] : 'Unknown Source';
        $line = array_key_exists('file', $trace[0]) && array_key_exists('line', $trace[0]) && $trace[0]['line'] ? $trace[0]['line'] : null;
        array_shift($trace);
    }
    $result = join("\n<br>", $result);
    if ($prev)
        $result  .= "\n<br>" . SN_jTraceEx($prev, $seen);

    return $result;
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
<!-- sometimes chrome refuses to scroll on reloading the same page -->
<button onclick='document.getElementById("newcell").scrollIntoView(true); return true;' id="scrollBtn" title="Go to bottom">Scroll Down</button>    
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
                            SN_jTraceEx($cell[3]) . '</font>'),
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
