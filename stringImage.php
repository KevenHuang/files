<?php
//制作字符流图片
header('content-type:text/html;charset=utf-8');
$image = imagecreatefromjpeg('./thinkphp/Public/images/shu.jpg');
//图片的宽高
$img_wid = imagesx($image);
$img_hei = imagesy($image);
echo '<body style="background-color:black">';
$str = '<div>';
for($i=0;$i<$img_hei;$i+=4){
	for($j=0;$j<$img_wid;$j+=2){
		$colorIndex = imagecolorat($image,$j,$i);
		$rgb = imagecolorsforindex($image,$colorIndex);
		$str .= sprintf('<font color="#%02x%02x%02x" style="font-size:1px;">#</font>',$rgb['red'],$rgb['green'],$rgb['blue']);
	}
	$str .= '<br/>';
}
$str .= '</div>';
imagedestroy($image);
echo $str;
echo '</body>';
