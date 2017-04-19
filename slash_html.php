<?php
/**
 * html标签清除程序
 * @copyright PSIKYO Corp. 2016
 */
include("config.php");
$fp=fopen(FINAL_FILE,"a+");
if($fp!=NULL)
{
    $f_size=filesize(FINAL_FILE);
    if($f_size>0)
    {
        $content=fread($fp,$f_size);
        fwrite($fp,htmlspecialchars($content));
        fclose($fp);
    }
}
else
{
    echo FINAL_FILE."can not be open.\n";
}
?>