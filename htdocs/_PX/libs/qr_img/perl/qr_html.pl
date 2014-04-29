#
# qr_html.pl   (qr_img.cgi ver.0.50g)
#
sub output_html{

$version_t_filename=$path."/qrvfr".$qrcode_version.".dat";

@cc=();
open(IN, $version_t_filename);
while (<IN>) {
   chop;
   push(@cc,$_);
}
close(IN);

print "Content-type:text/html\n\n";
print "<HTML>\n";

$i=0;
while ($i<$max_modules_1side){
   $j=0;
   while ($j<$max_modules_1side){
       $ccx=substr($cc[$i],$j,1);
       print "<img src=\x22".$img_path4html."/".$img_fn[ (($ccx) || (($matrix_content[$j][$i] & $mask_content) > 0)) ]."\x22>";
       $j++;
   }
   print "<BR>\n";
   $i++;
}
print "</HTML>";

}
1;
