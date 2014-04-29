#!/usr/bin/perl
#
#  QRcode image CGI    version 0.50g  (C)2000-2005,Y.Swetake
#
#
#  This program outputs a png image of "QRcode model 2". 
#  You cannot use a several functions of QRcode in this version. 
#  See README.txt .
#
#  This version supports "QRcode model2 version 1-40".
#
#  This program requires GD and GD.pm.
#
#  You must set '$path' the path to QR code data files ,
#  and 'image_path' the path to QR code frame image file.
# (to qrvN.png  N:version )
# -------------------------------------
#
# [useage]
#   qr_img.cgi?d=[data]&e=[(L,M,Q,H)]&s=[int]&v=[(1-9)]
#             (&m=[(1-16)]&n=[(2-16)](&p=[(0-255)],&o=[data]))
#
#   d= data         URL encoded data.
#   e= ECC level    L or M or Q or H   (default M)
#   s= module size  (dafault PNG:4 JPEG:8) no means in html mode
#   v= version      1-40 or Auto select if you do not set.
#   t= image type   H: HTML output , J:jpeg image , other: PNG image
#
#  structured append  m/n (experimental)
#   n= structure append n  (2-16)
#   m= structure append m  (1-16)
#   p= parity
#   o= original data (URL encoded data)  for calculate parity  
#
#
# This version supports command line mode.
# ex.1
#  $./qr_img.cgi e=L v=3 d=This+is+a+pen. > data.png
# ex.2
#  $./qr_img.cgi e=M v=6 < data.txt > data.png
#    You need not URL-encode data.txt contents. 
#
#
#
# THIS SOFTWARE IS PROVIDED BY Y.Swetake ``AS IS'' AND ANY EXPRESS OR
# IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES
# OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
# IN NO EVENT SHALL Y.Swetake OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT,
# INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES 
# (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
# LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)  HOWEVER CAUSED 
# AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
# OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE
# USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
#
#
#
#print "Content-type : text/html\n\n";   # for debug


#
# -------- setting area --------
#

 $path="./../data";          # ---You must set path to data files.


 # ---- for PNG or JPEG image
 $image_path="./../image";   # ---You must set path to QRcode frame images.

              
 # ---- for HTML mode
 $img_path4html="";          # ---You must set path to b.png and d.png.
                             # Default setting is document root.

 @img_fn=("b.png","d.png");  # In html mode,image size depends these files.


 $always_html_mode=0;        # If you set 1, always html mode.

 # ----
 $version_ul=40;             # upper limit for version.

#
# ------- setting area end --------
#


$query_string=$ENV{QUERY_STRING};
if (!$query_string){
    @arg=@ARGV;
} else {
    @arg= split( /&/ ,$query_string);
}

foreach $s (@arg){
    ($name,$value)= split (/=/ ,$s);
    $value =~ tr/+/ /;
    $value =~ s/%([0-9a-fA-F][0-9a-fA-F])/pack("C",hex($1))/eg;
    $query{$name}=$value;
}

$qrcode_data_string=$query{"d"};
$qrcode_error_correct=$query{"e"};
$qrcode_version=$query{"v"}; 
$qrcode_module_size=$query{"s"};
$qrcode_image_type=$query{"t"};

$qrcode_structureappend_n=$query{"n"};
$qrcode_structureappend_m=$query{"m"};
$qrcode_structureappend_parity=$query{"p"};
$qrcode_structureappend_originaldata=$query{"o"};

# ---- determine image type

if (($qrcode_image_type eq "J")||($qrcode_image_type eq "j")){
     $qrcode_image_type="jpeg";
     $qrcode_module_size= 8 unless ($qrcode_module_size>0);
} else {
     if (($qrcode_image_type eq "H")||($qrcode_image_type eq "h")){
         $qrcode_image_type="html";
         $qrcode_module_size= 4;
     } else {
         $qrcode_image_type="png";
         $qrcode_module_size= 4 unless ($qrcode_module_size>0);
     }
}

if (!$qrcode_data_string) {
    $qrcode_data_string=<STDIN>;
}
$data_length=length($qrcode_data_string);
if ($data_length<=0){
    print "Content-type: text/html\n\n";
    print "QRcode : data do not exist.";
    exit;
}

#---- structure append
$data_counter=0;
if ($qrcode_structureappend_n>1
 && $qrcode_structureappend_n<=16
 && $qrcode_structureappend_m<=16
 && $qrcode_structureappend_m>0){

    $data_value[0]=3;    #--- structureappend mode
    $data_bits[0]=4;

    $data_value[1]=$qrcode_structureappend_m-1;
    $data_bits[1]=4;

    $data_value[2]=$qrcode_structureappend_n-1;
    $data_bits[2]=4;

    $originaldata_length=length($qrcode_structureappend_originaldata);
    if ($originaldata_length>1){
        $i=0;
        $qrcode_structureappend_parity=0;
        while ($i<$originaldata_length){
            $qrcode_structureappend_parity=($qrcode_structureappend_parity ^ ord(substr($qrcode_structureappend_originaldata,$i,1)));
            $i++;
        }
    }

    $data_value[3]=$qrcode_structureappend_parity;
    $data_bits[3]=8;

    $data_counter=4;
}

# --- determine encode mode

$data_bits[$data_counter]=4;

if ($qrcode_data_string=~ /[^0-9]/){
    if ($qrcode_data_string =~ /[^0-9A-Z \$\*\%\+\-\.\/\:]/) {

     # --- 8bit byte mode

        @codeword_num_plus=(0,0,0,0,0,0,0,0,0,0,8,8,8,8,8,8,8,8,8,8,8,8,8,8,8,8,8,8,8,8,8,8,8,8,8,8,8,8,8,8,8);

        $data_value[$data_counter]=4;   # 8bit byte mode
        $data_counter++;
	$data_bits[$data_counter]=8;   # version 1-9
        $data_value[$data_counter]=$data_length;
        $codeword_num_counter_value=$data_counter;

	$i=0;
        $data_counter++;
	while ($i<$data_length){
	    $data_value[$data_counter]=ord(substr($qrcode_data_string,$i,1));
	    $data_bits[$data_counter]=8;
	    $data_counter++;
	    $i++;
        }
    } else {

     # ---- alphanumeric mode

        @codeword_num_plus=(0,0,0,0,0,0,0,0,0,0,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,4,4,4,4,4,4,4,4,4,4,4,4,4,4);

        $data_value[$data_counter]=2;            # alpha numeric mode
        $data_counter++;
	$data_value[$data_counter]=$data_length;
	$data_bits[$data_counter]=9;            #version 1-9

        $codeword_num_counter_value=$data_counter;

     %alphanumeric_character_hash=("0",0,"1",1,"2",2,"3",3,"4",4,"5",5,"6",6,"7",7,"8",8,"9",9,"A",10,
     "B",11,"C",12,"D",13,"E",14,"F",15,"G",16,"H",17,"I",18,"J",19,"K",20,
     "L",21,"M",22,"N",23,"O",24,"P",25,"Q",26,"R",27,"S",28,"T",29,"U",30,
     "V",31,"W",32,"X",33,"Y",34,"Z",35," ",36,"\$",37,"\%",38,"\*",39,
	  "\+",40,"\-",41,"\.",42,"\/",43,"\:",44);
	$i=0;
        $data_counter++;
	while ($i<$data_length){
	    if (($i %2)==0){
		$data_value[$data_counter]=$alphanumeric_character_hash{substr($qrcode_data_string,$i,1)};
		$data_bits[$data_counter]=6;
	    } else {
		$data_value[$data_counter]=$data_value[$data_counter]*45+$alphanumeric_character_hash{substr($qrcode_data_string,$i,1)};
		$data_bits[$data_counter]=11;
		$data_counter++;
	    }
	    $i++;
	}
    } 
} else {

 # ---- numeric mode

    @codeword_num_plus=(0,0,0,0,0,0,0,0,0,0,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,4,4,4,4,4,4,4,4,4,4,4,4,4,4);

    $data_value[$data_counter]=1;              # numeric mode
    $data_counter++;
    $data_value[$data_counter]=$data_length;   # length
    $data_bits[$data_counter]=10;             # version 1-9
    $codeword_num_counter_value=$data_counter;

    $i=0;
    $data_counter++;
    while ($i<$data_length){
        if (($i % 3)==0){
            $data_value[$data_counter]=substr($qrcode_data_string,$i,1);
            $data_bits[$data_counter]=4;
        } else {
	    $data_value[$data_counter]=$data_value[$data_counter]*10+substr($qrcode_data_string,$i,1);
	    if (($i % 3)==1){
		$data_bits[$data_counter]=7;
	    } else {
		$data_bits[$data_counter]=10;
		$data_counter++;
	    }
        }
        $i++;
    }
}
if ($data_bits[$data_counter]>0) {
    $data_counter++;
}
$i=0;
$total_data_bits=0;
while($i<$data_counter){
    $total_data_bits+=$data_bits[$i];
    $i++;
}

%ecc_character_hash=("L",1,"l",1,"M",0,"m",0,"Q",3,"q",3,"H",2,"h",2);

$ecc=$ecc_character_hash{$qrcode_error_correct}; 

if (!$ecc){$ecc=0;}

@max_data_bits_array=(
0,128,224,352,512,688,864,992,1232,1456,1728,
2032,2320,2672,2920,3320,3624,4056,4504,5016,5352,
5712,6256,6880,7312,8000,8496,9024,9544,10136,10984,
11640,12328,13048,13800,14496,15312,15936,16816,17728,18672,

152,272,440,640,864,1088,1248,1552,1856,2192,
2592,2960,3424,3688,4184,4712,5176,5768,6360,6888,
7456,8048,8752,9392,10208,10960,11744,12248,13048,13880,
14744,15640,16568,17528,18448,19472,20528,21616,22496,23648,

72,128,208,288,368,480,528,688,800,976,
1120,1264,1440,1576,1784,2024,2264,2504,2728,3080,
3248,3536,3712,4112,4304,4768,5024,5288,5608,5960,
6344,6760,7208,7688,7888,8432,8768,9136,9776,10208,

104,176,272,384,496,608,704,880,1056,1232,
1440,1648,1952,2088,2360,2600,2936,3176,3560,3880,
4096,4544,4912,5312,5744,6032,6464,6968,7288,7880,
8264,8920,9368,9848,10288,10832,11408,12016,12656,13328
    );

if ($qrcode_version =~ m/[^0-9]/){
    $qrcode_version="";
}

if (!$qrcode_version){        #--- auto version select
    $i=1+40*$ecc;
    $j=$i+39;
    $qrcode_version=1;
    while ($i<=$j){
        if (($max_data_bits_array[$i])>=$total_data_bits+$codeword_num_plus[$qrcode_version]){
            $max_data_bits=$max_data_bits_array[$i];
            last;
        }
        $i++;
        $qrcode_version++;
    }
} else {
    $max_data_bits=$max_data_bits_array[$qrcode_version+40*$ecc];
}
if ($qrcode_version>$version_ul){
    print "Content-type: text/html\n\n";
    print "QRcode : too large version.";
    exit;
}

$total_data_bits+=$codeword_num_plus[$qrcode_version];
$data_bits[$codeword_num_counter_value]+=$codeword_num_plus[$qrcode_version];

@max_codewords_array=(0,26,44,70,100,134,172,196,242,
292,346,404,466,532,581,655,733,815,901,991,1085,1156,
1258,1364,1474,1588,1706,1828,1921,2051,2185,2323,2465,
2611,2761,2876,3034,3196,3362,3532,3706);

$max_codewords=$max_codewords_array[$qrcode_version];
$max_modules_1side=17+($qrcode_version <<2);

@matrix_remain_bit=(0,0,7,7,7,7,7,0,0,0,0,0,0,0,3,3,3,3,3,3,3,
4,4,4,4,4,4,4,3,3,3,3,3,3,3,0,0,0,0,0,0);

# ---- read version ECC data file.

$byte_num=$matrix_remain_bit[$qrcode_version]+($max_codewords << 3);
$filename=$path."/qrv".$qrcode_version."_".$ecc.".dat";
open (IN,$filename);
    binmode(IN);
    sysread(IN,$matx,$byte_num);
    sysread(IN,$maty,$byte_num);
    sysread(IN,$masks,$byte_num);
    sysread(IN,$fi_x,15);
    sysread(IN,$fi_y,15);
    sysread(IN,$rs_ecc_codewords_chr,1);
    $rs_ecc_codewords=ord($rs_ecc_codewords_chr);
    sysread(IN,$rso,128);
close(IN);

@matrix_x_array=unpack("C*",$matx);
@matrix_y_array=unpack("C*",$maty);
@mask_array=unpack("C*",$masks);

@rs_block_order=unpack("C*",$rso);

@format_information_x2=unpack("C*",$fi_x);
@format_information_y2=unpack("C*",$fi_y);

@format_information_x1=(0,1,2,3,4,5,7,8,8,8,8,8,8,8,8);
@format_information_y1=(8,8,8,8,8,8,8,8,7,5,4,3,2,1,0);


$max_data_codewords=($max_data_bits >> 3);

$filename = $path."/rsc".$rs_ecc_codewords.".dat";

open (IN2, $filename);
    binmode(IN2);
    $i=0;
    while ($i<256) {
        sysread(IN2,$rs_cal_table_array[$i],$rs_ecc_codewords);
        $i++;
    }
close (IN2);

# ----  set teminator 

if ($total_data_bits<=$max_data_bits-4){
    $data_value[$data_counter]=0;
    $data_bits[$data_counter]=4;
} else {
    if ($total_data_bits<$max_data_bits){
	$data_value[$data_counter]=0;
        $data_bits[$data_counter]=$max_data_bits-$total_data_bits;
    } else {
        if ($total_data_bits>$max_data_bits){

	    print "Content-type: text/html\n\n";
            print "QRcode : Overflow error";
	    exit;
        }
    }
}

# ---- divide data per 8bits

$i=0;
$codewords_counter=0;
$codewords[0]=0;
$remaining_bits=8;

while ($i<=$data_counter) {
    $buffer=$data_value[$i];
    $buffer_bits=$data_bits[$i];

    $flag=1;
    while ($flag) {
        if ($remaining_bits>$buffer_bits){  
            $codewords[$codewords_counter]=(($codewords[$codewords_counter]<<$buffer_bits) | $buffer);
            $remaining_bits-=$buffer_bits;
            $flag=0;
        } else {
            $buffer_bits-=$remaining_bits;
            $codewords[$codewords_counter]=(($codewords[$codewords_counter] << $remaining_bits) | ($buffer >> $buffer_bits));
            if ($buffer_bits==0) {
                $flag=0;
            } else {
                $buffer= ($buffer & ((1 << $buffer_bits)-1) );
                $flag=1;   
            } 
            $codewords_counter++;
            if ($codewords_counter<$max_data_codewords-1){
                $codewords[$codewords_counter]=0;
            }
            $remaining_bits=8;
        }
    }
    $i++;
}
if ($remaining_bits!=8) {
    $codewords[$codewords_counter]=$codewords[$codewords_counter] << $remaining_bits;
} else {
    $codewords_counter--;
}


# ----  set padding character

if ($codewords_counter<$max_data_codewords-1){
    $flag=1;
    while ($codewords_counter<$max_data_codewords-1){
        $codewords_counter++;
        if ($flag==1) {
            $codewords[$codewords_counter]=236;
        } else {
            $codewords[$codewords_counter]=17;
        }
        $flag=$flag*(-1);
    }
}



# ----  RS-ECC prepare

$i=0;
$j=0;
$rs_block_number=0;
@rs_temp=();
while($i<$max_data_codewords){
    $rs_temp[$rs_block_number].=chr($codewords[$i]);
    $j++;
    if ($j>=$rs_block_order[$rs_block_number]-$rs_ecc_codewords){
	$j=0;
	$rs_block_number++;
    }
    $i++;
}


#
# RS-ECC main
#

$rs_block_number=0;
$rs_block_order_num=$#rs_block_order+1;

while ($rs_block_number<=$rs_block_order_num){
    $rs_codewords=$rs_block_order[$rs_block_number];
    $rs_codewords=~s/\n//g;
    $rs_data_codewords=$rs_codewords-$rs_ecc_codewords;
    $rstemp=$rs_temp[$rs_block_number].(chr(0) x $rs_ecc_codewords);

    $j=$rs_data_codewords;
    while($j>0){
        $first=ord(substr($rstemp,0,1));

        if ($first!=0){
            $left_chr=substr($rstemp,1);
            $cal=$rs_cal_table_array[$first];
            $rstemp=$left_chr ^ $cal;
        } else {
            $rstemp=substr($rstemp,1);        
        }
        $j--;
    }

    push(@codewords,unpack("C*",$rstemp));

    $rs_block_number++;
}

#
# ---- put data
#

# ---- flash matrix

$i=0;
while ($i<$max_modules_1side){
    $j=0;
    while ($j<$max_modules_1side){
        $matrix_content[$j][$i]=0;
        $j++;
    }
    $i++;
}


# ---- attach data
$i=0;
while ($i<$max_codewords){
    $codeword_i=$codewords[$i];
    $j=7;
    while ($j>=0){
        $codeword_bits_number=($i << 3)+$j;
        $matrix_content[ $matrix_x_array[$codeword_bits_number] ][ $matrix_y_array[$codeword_bits_number] ]=((255*($codeword_i & 1)) ^ $mask_array[$codeword_bits_number] ); 
        $codeword_i= $codeword_i >> 1;
        $j--;
    }
    $i++;
}


$matrix_remain=$matrix_remain_bit[$qrcode_version];
while ($matrix_remain){
    $remain_bit_temp = $matrix_remain + ( $max_codewords <<3);
    $matrix_content[ $matrix_x_array[$remain_bit_temp] ][ $matrix_y_array[$remain_bit_temp] ]  =  ( 255 ^ $mask_array[$remain_bit_temp] );
    $matrix_remain--;
}


# ---- mask select


$min_demerit_score=0;
    $hor_master="";
    $ver_master="";
    $k=0;
    while($k<$max_modules_1side){
        $l=0;
        while($l<$max_modules_1side){
            $hor_master=$hor_master.chr($matrix_content[$l][$k]);
            $ver_master=$ver_master.chr($matrix_content[$k][$l]);
            $l++;
        }
        $k++;
    }

$i=0;
$all_matrix=$max_modules_1side*$max_modules_1side;
while ($i<8){
    $demerit_n1=0;
    @ptn_temp=();
    $bit= 1<< $i;
    $bit_r= (~$bit) & 255;

    $bit_mask=chr($bit) x $all_matrix;
    $hor = $hor_master & $bit_mask;
    $ver = $ver_master & $bit_mask;

    $ver_and = ((chr(170) x $max_modules_1side).$ver) & ($ver.(chr(170) x $max_modules_1side));
    $ver_or = ((chr(170) x $max_modules_1side).$ver) | ($ver.(chr(170) x $max_modules_1side));

    $hor= ~$hor;
    $ver= ~$ver;
    $ver_and= ~$ver_and;
    $ver_or = ~$ver_or;

substr($ver_and,$all_matrix,0)=chr(170);
substr($ver_or,$all_matrix,0)=chr(170);

$k=$max_modules_1side-1;

while ($k){
    substr($hor,$k * $max_modules_1side,0)=chr(170);
    substr($ver,$k * $max_modules_1side,0)=chr(170);
    substr($ver_and,$k * $max_modules_1side,0)=chr(170);
    substr($ver_or,$k * $max_modules_1side,0)=chr(170);
$k--;
}

    $hor=$hor.chr(170).$ver;
    $n1_search=(chr(255) x 5)."+|".(chr($bit_r) x 5)."+";
    $n2_search1=chr($bit_r).chr($bit_r)."+";
    $n2_search2=chr(255).chr(255)."+";
    $n3_search=chr($bit_r).chr(255).chr($bit_r).chr($bit_r).chr($bit_r).chr(255).chr($bit_r);
    $n4_search=chr($bit_r);

    $hor_temp=$hor;
    $demerit_n3=( $hor_temp =~ s/$n3_search//g )*40;

    $demerit_n4=int(abs(( (100* ( ($ver=~s/$n4_search//g) /($byte_num)) )-50)/5))*10;


    $match_before_num=length($ver_and)+length($ver_or);
    $match_num= ( $ver_and =~ s/$n2_search1//g ) + ($ver_or =~ s/$n2_search2//g );
    $match_after_num=length($ver_and)+length($ver_or);
    $demerit_n2=($match_before_num-$match_after_num-$match_num)*3;


    $match_before_num=length($hor);
    $match_num= ( $hor =~ s/$n1_search//g );
    $match_after_num=length($hor);
    $demerit_n1=$match_before_num - $match_after_num - ($match_num << 1);


    $demerit_score=$demerit_n1+$demerit_n2+$demerit_n3+$demerit_n4;

    if ($demerit_score<=$min_demerit_score || $i==0){
        $mask_number=$i;
        $min_demerit_score=$demerit_score;
    }
    $i++;
}

$mask_content=1 << $mask_number;

# ---- format information


$format_information_value=(($ecc << 3) | $mask_number);
@format_information_array=("101010000010010","101000100100101",
"101111001111100","101101101001011","100010111111001","100000011001110",
"100111110010111","100101010100000","111011111000100","111001011110011",
"111110110101010","111100010011101","110011000101111","110001100011000",
"110110001000001","110100101110110","001011010001001","001001110111110",
"001110011100111","001100111010000","000011101100010","000001001010101",
"000110100001100","000100000111011","011010101011111","011000001101000",
"011111100110001","011101000000110","010010010110100","010000110000011",
"010111011011010","010101111101101");

$i=0;
while ($i<15){
    $content=substr($format_information_array[$format_information_value],$i,1);
    $matrix_content[$format_information_x1[$i]][$format_information_y1[$i]]=$content * 255;
    $matrix_content[$format_information_x2[$i]][$format_information_y2[$i]]=$content * 255;
    $i++;
}

if ($qrcode_image_type eq "html" || $always_html_mode){
    $fname="./qr_html.pl";
    require $fname;
    &output_html;
} else {
    $fname="./qr_image.pl";
    require $fname;
    &output_image;
}
