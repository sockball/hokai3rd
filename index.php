<?php

	/** 
	  * 崩坏3rd 简易扩充补给模拟器
	  * by sockball

	*/

	/**
	  * 概率分布
	  * S级1.5, A级4.5, 其他3A 3.0
	  * S级碎片2.5, A级碎片7.5, 其他3A 3.0
	  * 技能材料25, 其他13.67
	*/
function p($param)
{
	echo '<pre><br>';
	echo '-----------------------<br>';
	print_r($param);
	echo '-----------------------';
	echo '</pre>';
	exit;
}

function v($param)
{
	echo '<pre><br>';
	echo '-----------------------<br>';
	var_dump($param);
	echo '-----------------------';
	echo '<pre>';
	exit;
}

function randomFloat($min = 0, $max = 10)  
{  
   $num = $min + mt_rand() / mt_getrandmax() * ($max - $min);  

   return sprintf('%.2f', $num);
}

function single($bases)
{
	$sum = array_sum($bases);

	foreach ($bases as $k => $base)
	{
		$rand = randomFloat(1, $sum);	
		if($rand <= $base)
			break;
		else
			$sum -= $base;
	}

	return $k; 
}

/**
  * 保底算法
*/
function singleMinimum()
{
	$bases = [
		'0' => 1.5,
		'1' => 4.5,
		'2' => 3.0,
		'3' => 3.0,
		'4' => 3.0,
	];

	return single($bases); 
}

session_start();

require_once 'Redis.class.php';
$redis 		= new RedisVote('127.0.0.1', '6379', '');
$prizeArray = $redis->get('prizeArray');
$bases	    = $redis->get('bases');

$post = $_POST;
// session_destroy();
if(isset($post['submit']) && $post['submit'] == 'yes')
{
	//处理post请求
	if($post['type'] == 'single')
	{
		//单抽
		if(isset($_SESSION['num']) && $_SESSION['num'] == 9)
		{
			$key = singleMinimum($bases);
			$_SESSION['num'] = 0;
		}
		else
		{
			$key = single($bases);
			if(!isset($_SESSION['num']))
				$_SESSION['num'] = 1;
			elseif($key < 5)
			{
				//重新计算保底
				$_SESSION['num'] = 0;
			}
			else
				$_SESSION['num']++;
		}

		$prize = $prizeArray[ $key ];
		$res   = ['error' => 0, 'msg' => $prize['prize'], 'class' => $prize['color']]; 
	}
	elseif($post['type'] == 'ten')
	{
		//10连
		$num = isset($_SESSION['num']) ? $_SESSION['num'] : 0;
		$minimum = 9 - $num;
		$prizes  = [];
		$classes = [];
		for($i = 0; $i < 10; $i++)
		{
			if($i == $minimum)
			{
				$key = singleMinimum($bases);
				$_SESSION['num'] = 0;
			}
			else
			{
				$key = single($bases);
				if(!isset($_SESSION['num']))
					$_SESSION['num'] = 1;
				elseif($key < 5)
				{
					//重新计算保底
					$_SESSION['num'] = 0;
					$minimum = 0;
				}
				else
					$_SESSION['num']++;
			}

			$prize 	   = $prizeArray[ $key ];
			$prizes[]  = $prize['prize'];
			$classes[] = $prize['color']; 
		}

		$res = ['error' => 0, 'msg' => $prizes, 'class' => $classes]; 

	}
/*	elseif($post['type'] == 'clear')
	{
		//重置保底 or 清除缓存
		$_SESSION['num'] = 0;
		$res = ['error' => 0, 'msg' => '清除成功'];
	}*/
	else
		$res = ['error' => 1, 'msg' => 'what a fuck?'];

	exit(json_encode($res, JSON_UNESCAPED_UNICODE));
}
else
{
	//初始化页面

	// $redis->flush();
	// v($bases);
	if($prizeArray === false)
	{
		$prizeArray = [
				'0'  => ['prize' => 'S级角色卡', 	 'base' => 1.5,   'color' => 'layui-bg-orange'],
				'1'  => ['prize' => 'A级角色卡(UP)', 'base' => 4.5,   'color' => 'layui-bg-blue'],
				'2'	 => ['prize' => 'A级角色卡1',	 'base' => 3.0,   'color' => 'layui-bg-blue'], 
				'3'	 => ['prize' => 'A级角色卡2',	 'base' => 3.0,   'color' => 'layui-bg-blue'],
				'4'	 => ['prize' => 'A级角色卡3',	 'base' => 3.0,   'color' => 'layui-bg-blue'],
				'5'	 => ['prize' => 'S级碎片',  	 'base' => 2.5,	  'color' => 'layui-bg-red'],
				'6'	 => ['prize' => 'A级碎片(UP)',	 'base' => 7.5,	  'color' => 'layui-bg-green'],
				'7'	 => ['prize' => 'A级碎片1',		 'base' => 3.0,   'color' => 'layui-bg-green'],
				'8'	 => ['prize' => 'A级碎片2',		 'base' => 3.0,	  'color' => 'layui-bg-green'],
				'9'	 => ['prize' => 'A级碎片3',		 'base' => 3.0,   'color' => 'layui-bg-green'],
				'10' => ['prize' => '技能材料',		 'base' => 25,	  'color' => ''],
				'11' => ['prize' => '其他玩意1',	 'base' => 13.67, 'color' => ''],
				'12' => ['prize' => '其他玩意2',	 'base' => 13.67, 'color' => ''],
				'13' => ['prize' => '其他玩意3',	 'base' => 13.67, 'color' => ''],
		];
		$bases = array_column($prizeArray, 'base');

		$redis->set('prizeArray', $prizeArray, 3600);
		$redis->set('bases',   $bases,   3600);
	}
}
?>

<!DOCTYPE html>
<html lang='en'>
<head>
	<meta charset='UTF-8'>
	<title>崩坏3rd 简易扩充补给模拟器</title>
	<link rel='stylesheet' href='./assets/layui.css'>
	<link rel='shortcut icon' href='./assets/moe.ico' >
</head>
<body>
	
<div class='layui-container'>  
	<div class='layui-row layui-col-space30'>
		<div class='layui-col-md9' id='info'>
			<blockquote class='layui-elem-quote explain' id='init'>
				<p>暂无抽奖信息</p>
			</blockquote>
		</div>

		<div class='layui-col-md3'>
			<button class='layui-btn layui-btn-normal' id='single'>单抽</button>
			<button class='layui-btn layui-btn-warm' id='ten'>10连</button>
			<!-- <button class='layui-btn' id='clear'>重置保底</button>			 -->
		</div>

	</div>
</div>

	<script src='./assets/jquery.js'></script>

	<script>
		var html = '<blockquote class="layui-elem-quote explain ';

		$('#single').on('click', function(){
			let children = $('#info').children();

			$.post('./index.php', {submit: 'yes', type: 'single'}, function(res) {
				if(res.error < 1)
				{
					let str = html;
					str += res.class + '"><p>';
					str += res.msg;
					str += '</p></blockquote>';

					//移除最上面的
					if($('#init').length > 0 || children.length == 10)
						children.eq(0).remove();

					$('#info').append(str);
				}
				else
					alert('二回死ね!!');
			}, 'json');
		});

		$('#ten').on('click', function(){
			let container = $('#info');

			$.post('./index.php', {submit: 'yes', type: 'ten'}, function(res) {
				if(res.error < 1)
				{
					let base = html;
					let str  = '';
					for(let i = 0; i < 10; i++)
					{
						str += base;
						str += res.class[i] + '"><p>';
						str += res.msg[i];
						str += '</p></blockquote>';
					}
					container.empty();
					container.append(str);
				}
				else
					alert('十回死ね!!');
			}, 'json');
		});

	</script>
</body>
</html>