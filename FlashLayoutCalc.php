#!/usr/bin/php
<?php
$copyright = "
Flash layout calculator. (C) Wojciech Nizinski 2012-2015 
";
?>

<?php
// Script requires php5-cli package to run
//       sudo apt-get install php5-cli
?>

<?php
    include "board_config.php";
?>

<?php
    $arSizeSuffixes = array 
    (
    		//       0           1                   2
    		//	   out suff		value			
    		array ("Bytes", 	1,				"B"	),
    		array ("kB",		pow(2,10),			"K"	),
    		array ("MB",		pow(2,20),			"M"	),
    		array ("GB",		pow(2,30),			"G" ),
    		array ("TB",		pow(2,40),			"T"	),
    		array ("PB",		pow(2,50),			"P"	),
    );
    

/**
 * 
 * @param unknown $letter
 */
function get_multiply_from_letter ($letter)
{
	global $arSizeSuffixes;
	foreach ($arSizeSuffixes as $arSizeSuffix)
	{
		if ($arSizeSuffix[2] == $letter)
		{
			return $arSizeSuffix[1];
		}	
	}
	return 1;
}    

/** 
 * Converts human string to size in bytes
*/
function  read_human_value ($inputString)
{
	$n = trim ($inputString);
	$n = str_replace (',', '.', $n); // convert decimal point
	$n = strtoupper ($n);

	// match 0-9 and dot one or more times
	// match spaces zero or more times
	// match K or M or G zero or one time
	// match B zero or one time
	if (! preg_match("/^(?P<value>[0-9\.]+)\ *(?P<size>[KMG]?)(?P<bytes>B?)/", $n, $matches))
	{
		echo "Syntax error in '" . $inputString . " '\n";
		die;
	}
	$value = floatval($matches['value']);
	if ($matches['size'])
	{
		$value = $value * get_multiply_from_letter($matches['size']);
	}
	
	return floor($value);
}

/**
 * 
 * @param unknown $value
 * @return string
 */
function get_human_value ($value)
{
	global $arSizeSuffixes;
	return $value ? round ($value/pow(1024, ($i = floor(log($value, 1024)))), 2) . $arSizeSuffixes[$i][0] : '0 Bytes';
}





/**
 *  
 *  main program 
 */

$offset = 0;
$sum = 0;
$mtd_index = 0;

$FlashSizeBytes = read_human_value($FlashSize);
$FlashEraseBlockSize = read_human_value ($FlashEraseBlockSize);

$FlashNumOfEraseBlocks = $FlashSizeBytes / $FlashEraseBlockSize;
printf ($copyright);
printf ("\nPartitions layout for flash memory:\n");
printf ("\tsize ................: %10s = %10d B = 0x%08X B\n", $FlashSize, $FlashSizeBytes, $FlashSizeBytes);
printf ("\terase block size ....: %10s = %10d B = 0x%08X B\n", get_human_value($FlashEraseBlockSize), $FlashEraseBlockSize, $FlashEraseBlockSize);
printf ("\tnumber of blocks ....: %10s   %10d   = 0x%08X\n",   "",                                    $FlashNumOfEraseBlocks, $FlashNumOfEraseBlocks);
printf ("\n");

$dt ="/* Device tree partitions description for flash generated by*/\n";
$dt.="/* ". $copyright . "*/\n";
$uboot = $dt;

foreach ($arFlashLayout as $partname => $partdesc)
{
	if ($mtd_index==0)                       $first_mtd=true; else $first_mtd=false;
	if ($mtd_index==count($arFlashLayout)-1) $last_mtd=true;  else $last_mtd=false;

	$part_size = read_human_value ($partdesc);
	echo "mtd" . $mtd_index . " '" . $partname . "' wanted size: '" . $part_size ."' (".$partdesc .")\n";
	
	// check minimum size of partition
	if ($part_size < $FlashEraseBlockSize)
	{
		$part_size = $FlashEraseBlockSize;
		printf ("\t! size too small increasing size to %d\n", $part_size);
	}
	// check multiply of erase block (sector size)
	$rest = $part_size % $FlashEraseBlockSize;
	if ( $rest != 0)
	{
		printf ("\t! size not in erase block boundary, increasing to %d\n", $part_size);
		$part_size += $rest;
	}
	

	printf ("\toffset: 0x%08X %10d   (%6s) (%5s ebl)\n", $offset,	$offset,	get_human_value($offset),    ($offset/$FlashEraseBlockSize));
	printf ("\tsize:   0x%08X %10d   (%6s) (%5s ebl)\n", $part_size,	$part_size,	get_human_value($part_size), ($part_size/$FlashEraseBlockSize));
	echo "----------------------------------------------------------------------\n";

	$dt.= sprintf ("partition@%-3d { reg = <0x%08X 0x%08X>; label = \"%s\";  };\n", 
			$mtd_index, $offset, $part_size, $partname);
	

        $uboot.= sprintf ("/* %3d */    ", $mtd_index);
	$uboot.= sprintf ("\"%dm(%s)", ($part_size/1024/1024), $partname);
	if (!$last_mtd) $uboot.=",";
	$uboot.="\"";
	if (!$last_mtd) $uboot.="\\";
	//if ($last_mtd) $uboot.=";";
	$uboot.="\n";


	$sum += $part_size;
	$offset += $part_size;
	$mtd_index++;
}
	echo "help: ebl - erase blocks\n";
	echo "\n";
echo "Total length: " . get_human_value($sum) . " (". $sum .")\n";
if ($sum>$FlashSizeBytes)
{
	echo "Flash size exceed! Maximum size is: ". $FlashSizeBytes . "\n";
}
printf ("Unused space: %d B (%s).\n",$FlashSizeBytes - $sum, get_human_value($FlashSizeBytes - $sum));

print "\n\n";
print $dt;

print "\n\n";
print $uboot;

?>