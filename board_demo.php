<?php
    $FlashSize = "8MB";
    $FlashEraseBlockSize = "4k";

    //$BadBlocksMarginPercent = "2";<---->// (in percent) 2% is common for NAND flashes
    //$BadBlocksMarginMin     = "2";<---->// in erase blocks, minmum eraseblocks left for bad block handling

    $arFlashLayout = array 
    (
		"loader"	=>	"4k",
		"Bootloader"	=>	"124k",
		
		"Recovery"	=>	"800k",
		
		"Linux1"	=>	"2500k",
		"FS1"		=>	"1112k",
		
		"Linux2"	=>	"2500k",
		"FS2"		=>	"1112k",

    	
		"Factory"	=>	"32k",
		"Env"		=>	"8k",
    );
?>