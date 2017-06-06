<?php
header('content-type:text/html;charset=utf-8');
$data = array(
array('id'=>1,'name'=>'手机','pid'=>0),
array('id'=>2,'name'=>'小米手机','pid'=>1),
array('id'=>3,'name'=>'华为手机','pid'=>1),
array('id'=>4,'name'=>'家电','pid'=>0),
array('id'=>5,'name'=>'电冰箱','pid'=>4),
array('id'=>6,'name'=>'电视机','pid'=>4),
array('id'=>7,'name'=>'空调','pid'=>4),
array('id'=>8,'name'=>'手机配件','pid'=>0),
array('id'=>9,'name'=>'手机电池','pid'=>8),
array('id'=>10,'name'=>'红米手机','pid'=>1),
array('id'=>11,'name'=>'手机后盖','pid'=>8),
array('id'=>12,'name'=>'饮水机','pid'=>4),
array('id'=>13,'name'=>'手机贴膜','pid'=>8),
array('id'=>14,'name'=>'三星手机','pid'=>1),
array('id'=>15,'name'=>'家具','pid'=>0),
array('id'=>16,'name'=>'电视盒子','pid'=>4),
array('id'=>17,'name'=>'桌子','pid'=>15),
array('id'=>18,'name'=>'酷派手机','pid'=>1),
array('id'=>19,'name'=>'小米电视','pid'=>6)
);

function tree($data,$pid=0,$level=1){
	static $tree = [];
	foreach($data as $key=>$val){
		if($val['pid']==$pid){
			$val['level'] = $level;
			$parentId = $val['id'];	
			$tree[] = $val;
			unset($data[$key]);
			tree($data,$parentId);
		}
	}
	return $tree;
}
echo '<pre>';
print_r(tree($data));
