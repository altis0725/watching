<?php

$old = file("ruka0725_Mentions.dat");
print_r($old);
echo "<br>";

$old = file("ruka0725_gfsd.dat");
print_r($old);
echo "<br>";

$old = file("ruka0725_あdsf.dat");
print_r($old);
echo "<br>";

$handle = @fopen("ruka0725_えいあw.dat","r");
$old = fgets($handle,4096);
echo $old;
echo "<br>";
fclose($handle);

?>
