#
#   qr_image.pl    (qr_img.cgi ver.0.50g)
#
sub output_image{

use GD;
$qrcode_image_size=$qrcode_module_size*($max_modules_1side+8);
if ($qrcode_image_size>1480){
    print "Content-type: text/html\n\n";
    print "QRcode : too large image size.";
    exit;
}
$image_path=$image_path."/qrv".$qrcode_version.".png";
$output_image=GD::Image->new($qrcode_image_size,$qrcode_image_size);
$base_image=GD::Image->newFromPng($image_path);

$color[1]=$base_image->colorAllocate(0,0,0);
$color[0]=$base_image->colorAllocate(255,255,255);
$mxe=4+$max_modules_1side;

$i=4;
$ii=0;
while ($i<$mxe){
 $j=4;
 $jj=0;
 while ($j<$mxe){
     if ($matrix_content[$ii][$jj] & $mask_content){
       $base_image->setPixel($i,$j,$color[1]);
   }
  $j++;
  $jj++;
 }
 $i++;
 $ii++;
}

#
#--- output image
#

$output_image->copyResized($base_image,0,0,0,0,$qrcode_image_size,$qrcode_image_size,$max_modules_1side+8,$max_modules_1side+8);

if ($query_string){
    print "Content-type: image/".$qrcode_image_type."\n\n";
}

if ($qrcode_image_type eq "jpeg"){
    binmode STDOUT;
    print $output_image->jpeg;
} else {
    binmode STDOUT;
    print $output_image->png;
}

}
1;
