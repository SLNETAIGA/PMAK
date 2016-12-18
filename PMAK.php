<?php
	
	/**********************************************************
     * PMAK - PhpMAKe                                         *
	 * (c) 2016. SLNETAIGA and KateJa                         *
	 **********************************************************
	 * Some custom makefiles interpreter writed on PHP CLI    *
	 * Usage: php7<?php
	
	/**********************************************************
     * PMAK - PhpMAKe                                         *
	 * (c) 2016. SLNETAIGA and KateJa                         *
	 **********************************************************
	 * Some custom makefiles interpreter writed on PHP CLI    *
	 * Usage: php7 PMAK.php <makefile.mak>                    *
	 * Licensed under MIT license                             *
	 **********************************************************/
	 
	// ?
	error_reporting(E_ERROR | E_PARSE);
	
	echo "PMAK - PhpMAKe\n";
	echo "(c) 2016. SLNETAIGA and KateJa\n\n";
	$file = $argv[1];
	if(!file_exists($file)){
		if(!file_exists("makefile.mak")){
			die("Usage: php7 PMAK.php <makefile.mak> or if 'makefile.mak' exists just php7 PMAK.php");
		} else {
			$file = "makefile.mak";
		}
		die("Usage: php7 PMAK.php <makefile.mak> or if 'makefile.mak' exists just php7 PMAK.php");
	}
	$source = file_get_contents($argv[1]);
	
	$source = preg_replace("/%.+?\n/","",$source,-1);
	$source = explode("\n",$source);
	$vars = array();
	$c = array();
	$labels = array();
	$aps = $argv;
	unset($aps[0]);
	unset($aps[1]);
	foreach($aps as $k=>$v){
		$c["?".$k] = $v;
	}
	
	// I'm like shit codes
	function joina($arr){
		global $vars,$c;
		unset($arr[0]);
		unset($arr[1]);
		foreach($arr as $k=>$v){
			if(isset($vars[$v])){
				$arr[$k] = $vars[$v];
			} else if(isset($c[$v])){
				$arr[$k] = $c[$v];
			}
		}
		return join(" ",$arr);
	}
	function joinb($arr){
		global $vars,$c;
		unset($arr[0]);
		foreach($arr as $k=>$v){
			if(isset($vars[$v])){
				$arr[$k] = $vars[$v];
			} else if(isset($c[$v])){
				$arr[$k] = $c[$v];
			}
			
		}
		return join(" ",$arr);
	}
	
	function evl($source){
	global $vars,$c,$labels;
	$b = -1;
	
	$opts = array("all_auto" => 1,
	"no_errorlevel" => 0,
	"allow_const_redef" => 0,
	);
	   for($i=0;$i<count($source);$i++){
			$ops = explode(" ",$source[$i]);
			$line = $i+1;
			
			if($ops[0] == "."){
				$labels[trim($ops[1])] = $i;
				$labels[trim($ops[1])]++;
			} else if(strtolower($ops[0]) == "target"){
				switch(strtolower(trim($ops[1]))){
					case "all_auto":
					$opts["all_auto"] = (int) trim($ops[2]);
					break;
					case "allow_const_redef":
					$opts["allow_const_redef"] = (int) trim($ops[2]);
					break;
					case "no_errorlevel":
					$opts["no_errorlevel"] = (int) trim($ops[2]);
					break;
					default:
					echo("\nPMAK: Unknown target '".trim($ops[1])."' at line $line\n");
					break;
				}
			} else if(is_string($ops[0]) and $ops[1] == ":="){
				if(isset($c[$ops[1]]) and !$opts["allow_const_redef"]){
					die("\nPMAK: Try to change constant at line $line\n");
				}
				$vars[$ops[0]] = joina($ops);
			} else if(is_string($ops[0]) and $ops[1] == "=" and !$opts["allow_const_redef"]){
				if(isset($c[$ops[1]])){
					die("\nPMAK: Try to change constant at line $line\n");
				}
				$vars[$ops[0]] = joina($ops);
			} else if(is_string($ops[0]) and strtolower($ops[1]) == "is"){
				if(isset($c[$ops[1]]) and !$opts["allow_const_redef"]){
					die("\nPMAK: Try to change constant at line $line\n");
				}
				$vars[$ops[0]] = joina($ops);
			} else if(strtolower($ops[0]) == "const"){
				if(isset($c[$ops[1]]) and !$opts["allow_const_redef"]){
					die("\nPMAK: Try to change constant at line $line\n");
				} else {
					$c[$ops[1]] = joina($ops);
				}
			} else if(strtolower($ops[0]) == "include"){
				$f = joinb($ops);
				if(!file_exists($f)){
					die("\nPMAK: Try to include no exists file\n");
				}
				evl(file_get_contents($f));
			}
		}
		for($i=0;$i<count($source);$i++){
			if($opts["all_auto"]){
				if(!isset($labels["all"])){
					die("\nPMAK: all_auto=1, but all not defined\n");
				}
				$i = $labels["all"];
				$opts["all_auto"] = 0;
			}
			$ops = explode(" ",$source[$i]);
			$line = $i+1;
			
			if($ops[0] == "@"){
				$f = joinb($ops);
				echo "\nPMAK: Executing job...\n";
				echo shell_exec($f);
				if(!$opts["no_errorlevel"]){
					$f = "if ERRORLEVEL 1 ( echo PMAK: Job returned ERRORLEVEL biggest then 1! ) else ( echo PMAK: Job ended success. )";
					echo shell_exec($f);
				}
			} else if($ops[0] == "!"){
				$f = joinb($ops);
				$i = $labels[$f];
			} else if($ops[0] == "$"){
				die(joinb($ops)."\n");
			} else if($ops[0] == "#"){
				echo joinb($ops)."\n";
			} else if($ops[0] == "^"){
				$f = joinb($ops);
				$b = $i;
				$i = $labels[trim($f)];
			} else if(strtolower($ops[0]) == "back"){
				$i = $b;
			} else if(strtolower($ops[0]) == "const" or $ops[0] == "\n" or $ops[0] == "\t" or $ops[0] == " " or $ops[0] == "." or $ops[0] == "target" or $ops[1] == ":=" or $ops[1] == "=" or strtolower($ops[1]) == "is" or strtolower($ops[0]) == "include"){
			} else {
				if(!empty(trim($ops[0]))){
					die("\nPMAK: Unknown terminal '".trim($ops[0])."' at line $line\n");
				}
			}
		}
	}
	
	evl($source); PMAK.php <makefile.mak>                    *
	 * Licensed under MIT license                             *
	 **********************************************************/
	 
	// ?
	error_reporting(E_ERROR | E_PARSE);
	
	echo "PMAK - PhpMAKe\n";
	echo "(c) 2016. SLNETAIGA and KateJa\n\n";
	$file = $argv[1];
	if(!file_exists($file)){
		if(!file_exists("makefile.mak")){
			die("Usage: php7 PMAK.php <makefile.mak> or if 'makefile.mak' exists just php7 PMAK.php");
		} else {
			$file = "makefile.mak";
		}
		die("Usage: php7 PMAK.php <makefile.mak> or if 'makefile.mak' exists just php7 PMAK.php");
	}
	$source = file_get_contents($argv[1]);
	
	$source = preg_replace("/%.+?\n/","",$source,-1);
	$source = explode("\n",$source);
	$vars = array();
	$c = array();
	$labels = array();
	$aps = $argv;
	unset($aps[0]);
	unset($aps[1]);
	foreach($aps as $k=>$v){
		$c["?".$k] = $v;
	}
	
	// I'm like shit codes
	function joina($arr){
		global $vars,$c;
		unset($arr[0]);
		unset($arr[1]);
		foreach($arr as $k=>$v){
			if(isset($vars[$v])){
				$arr[$k] = $vars[$v];
			} else if(isset($c[$v])){
				$arr[$k] = $c[$v];
			}
		}
		return join(" ",$arr);
	}
	function joinb($arr){
		global $vars,$c;
		unset($arr[0]);
		foreach($arr as $k=>$v){
			if(isset($vars[$v])){
				$arr[$k] = $vars[$v];
			} else if(isset($c[$v])){
				$arr[$k] = $c[$v];
			}
			
		}
		return join(" ",$arr);
	}
	
	function evl($source){
	global $vars,$c,$labels;
	
	$opts = array("all_auto" => 1,
	"no_errorlevel" => 0,
	"allow_const_redef" => 0,
	);
	   for($i=0;$i<count($source);$i++){
			$ops = explode(" ",$source[$i]);
			$line = $i+1;
			
			if($ops[0] == "."){
				$labels[trim($ops[1])] = $i;
				$labels[trim($ops[1])]++;
			} else if(strtolower($ops[0]) == "target"){
				switch(strtolower(trim($ops[1]))){
					case "all_auto":
					$opts["all_auto"] = (int) trim($ops[2]);
					break;
					case "allow_const_redef":
					$opts["allow_const_redef"] = (int) trim($ops[2]);
					break;
					case "no_errorlevel":
					$opts["no_errorlevel"] = (int) trim($ops[2]);
					break;
					default:
					echo("\nPMAK: Unknown target '".trim($ops[1])."' at line $line\n");
					break;
				}
			} else if(is_string($ops[0]) and $ops[1] == ":="){
				if(isset($c[$ops[1]]) and !$opts["allow_const_redef"]){
					die("\nPMAK: Try to change constant at line $line\n");
				}
				$vars[$ops[0]] = joina($ops);
			} else if(is_string($ops[0]) and $ops[1] == "=" and !$opts["allow_const_redef"]){
				if(isset($c[$ops[1]])){
					die("\nPMAK: Try to change constant at line $line\n");
				}
				$vars[$ops[0]] = joina($ops);
			} else if(is_string($ops[0]) and strtolower($ops[1]) == "is"){
				if(isset($c[$ops[1]]) and !$opts["allow_const_redef"]){
					die("\nPMAK: Try to change constant at line $line\n");
				}
				$vars[$ops[0]] = joina($ops);
			} else if(strtolower($ops[0]) == "const"){
				if(isset($c[$ops[1]]) and !$opts["allow_const_redef"]){
					die("\nPMAK: Try to change constant at line $line\n");
				} else {
					$c[$ops[1]] = joina($ops);
				}
			} else if(strtolower($ops[0]) == "include"){
				$f = joinb($ops);
				if(!file_exists($f)){
					die("\nPMAK: Try to include no exists file\n");
				}
				evl(file_get_contents($f));
			}
		}
		for($i=0;$i<count($source);$i++){
			if($opts["all_auto"]){
				if(!isset($labels["all"])){
					die("\nPMAK: all_auto=1, but all not defined\n");
				}
				$i = $labels["all"];
				$opts["all_auto"] = 0;
			}
			$ops = explode(" ",$source[$i]);
			$line = $i+1;
			
			if($ops[0] == "@"){
				$f = joinb($ops);
				echo "\nPMAK: Executing job...\n";
				echo shell_exec($f);
				if(!$opts["no_errorlevel"]){
					$f = "if ERRORLEVEL 1 ( echo PMAK: Job returned ERRORLEVEL biggest then 1! ) else ( echo PMAK: Job ended success. )";
					echo shell_exec($f);
				}
			} else if($ops[0] == "!"){
				$f = joinb($ops);
				$i = $labels[$f];
			} else if($ops[0] == "$"){
				die(joinb($ops)."\n");
			} else if($ops[0] == "#"){
				echo joinb($ops)."\n";
			} else if(strtolower($ops[0]) == "const" or $ops[0] == "\n" or $ops[0] == "\t" or $ops[0] == " " or $ops[0] == "." or $ops[0] == "target" or $ops[1] == ":=" or $ops[1] == "=" or strtolower($ops[1]) == "is" or strtolower($ops[0]) == "include"){
			} else {
				if(!empty(trim($ops[0]))){
					die("\nPMAK: Unknown terminal '".trim($ops[0])."' at line $line\n");
				}
			}
		}
	}
	
	evl($source);
