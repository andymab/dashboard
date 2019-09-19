<?php
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
class Dashboard {

    public function message($status = false, $message = false, $data = []) {
	$status = $status ? 'success' : 'error';
	$message = $message ? $message : ' данные';
	$data = $data ? $data : [];
	$arr = [
	    'status' => $status,
	    'message' => $message,
	    'data' => $data
	];

	return json_encode($arr, JSON_UNESCAPED_UNICODE);
    }

    public function turnVstat() {
	parse_str(filter_input(INPUT_POST, 'data'), $output);

	if (!$output)
	    return $this->message(false, __LINE__);

	file_put_contents('vmstat.csv', implode(',', $output));
	return $this->message(true, 'данные сохранены', $output);
    }

    public function turnVstatNow() {
	date_default_timezone_set('Europe/Moscow');
	list($sec, $amt) = explode(',', file_get_contents(__DIR__ . '/vmstat.csv'));

	$out = shell_exec("/usr/bin/vmstat -n " . $sec . " " . $amt . " -t 2>&1");
	file_put_contents('vmstat.txt', $out);

	return $this->chartVmstat();
    }

    public function chartVmstat() {
	date_default_timezone_set('Europe/Moscow');
	$data = [];
	$res = [];
	$filename = 'vmstat.txt';
	if (file_exists($filename)) {
	    $filetime = " окончен: " . date("d Y H:i:s.", filectime($filename));
	}

	if (($handle = fopen($filename, "r")) !== FALSE) {
	    fgets($handle, 1024);
	    fgets($handle, 1024);
	    while (!feof($handle)) {
		$line = trim(fgets($handle, 1024));
		list( $r, $b, $swpd, $free, $buff, $cache, $si, $so, $bi, $bo, $in, $cs, $us, $sy, $id, $wa, $st, $tm1, $tm2, $tm3) = array_pad(preg_split("/[\s]+/", $line), 20, '');

		if ($swpd === '') {
		    continue;
		}
		$data['si'][] = $si;
		$data['us'][] = $us;
		$data['sy'][] = $sy;
		$data['r'][] = $r;
		$data['b'][] = $b;
		$data['tmsi'][] = [intval(strtotime($tm1 . ' ' . $tm2 . ' ' . $tm3) . '000'), floatval($si)];
		$data['tmus'][] = [intval(strtotime($tm1 . ' ' . $tm2 . ' ' . $tm3) . '000'), floatval($us)];
		$data['tmsy'][] = [intval(strtotime($tm1 . ' ' . $tm2 . ' ' . $tm3) . '000'), floatval($sy)];
		$data['tmb'][] = [intval(strtotime($tm1 . ' ' . $tm2 . ' ' . $tm3) . '000'), floatval($b)];
	    }
	    fclose($handle);
	}

	/* Среднее значение колонки si
	  Максимальное значение колонки si
	  Максимальное значение колонки us
	  Максимальное значение колонки sy
	  Среднее значение колонки r
	  Среднее значение колонки b
	 * */
/////////////////////скорость обмена///////////////////////////
	$average = array_filter($data['si'], function($x) {
	    return $x !== '';
	});
	$si_avrg = round(array_sum($average) / count($average), 2);
	// 1 это 100% берем шкалу 100 


/*
	$res[] = [
	    'name' => 'RAM-HDD si avrg',
	    'zname' => $average . '~0',
	    'color' => $average < 1 ? '#64d471' : '#e8bd5c',
	    'y' => $average * 100 / 1,
	];
	*/
	/////////////////////скорость обмена///////////////////////////
	$max = array_filter($data['si'], function($x) {
	    return $x !== '';
	});
	$si_max = intval(max($max));
	/*
	$res[] = [
	    'name' => 'RAM-HDD si max',
	    'zname' => $max . '<20',
	    'color' => $max < 20 ? '#64d471' : '#e8bd5c',
	    'y' => $max * 100 / 20,
	];
	*/
	/////////////////////Загруженность процессоров///////////////////////////
	$max = array_filter($data['us'], function($x) {
	    return $x !== '';
	});
	$us_max = intval(max($max));
	/*
	$res[] = [
	    'name' => 'нагрузка CPU us',
	    'zname' => $max . '<70',
	    'color' => $max < 70 ? '#64d471' : '#e8bd5c',
	    'y' => $max * 100 / 70,
	];
	*/
	/////////////////////Загруженность процессоров///////////////////////////
	$max = array_filter($data['sy'], function($x) {
	    return $x !== '';
	});
	$sy_max = intval(max($max));
/*	
	$res[] = [
	    'name' => 'нагрузка CPU sy',
	    'zname' => $max . '<35',
	    'color' => $max < 35 ? '#64d471' : '#e8bd5c',
	    'y' => $max * 100 / 35,
	];
*/
	/////////////////////Очередь к CPU///////////////////////////
	$amtCpu = 2 * 24;
	$average = array_filter($data['r'], function($x) {
	    return $x !== '';
	});
	$r_average = round(array_sum($average) / count($average), 2);
	$res[] = [
	    'name' => 'Очередь к CPU r',
	    'zname' => $r_average . '<' . $amtCpu,
	    'color' => $r_average < $amtCpu ? '#64d471' : '#e8bd5c',
	    'y' => $r_average * 100 / $amtCpu,
	];
	/////////////////////Очередь к HDD///////////////////////////
	$amtHDD = 2 * 4;
	$average = array_filter($data['b'], function($x) {
	    return $x !== '';
	});

	$b_average = array_sum($average) ? round(array_sum($average) / count($average), 2) : 0;
	
	
/*	
	$res[] = [
	    'name' => 'Очередь к HDD b',
	    'zname' => $average . '<' . $amtHDD,
	    'color' => $average < $amtHDD ? '#64d471' : '#e8bd5c',
	    'y' => $average * 100 / $amtHDD,
	];
*/	
	$si = [
	    'data'=>$data['tmsi'],
	    'message'=>'<b>(SI)</b> RAM-HDD avrg~'.$si_avrg.' max :'.$si_max,
	    'yaxis'=> 'avrg~0 max<20'
	    ];
	$us = [
	    'data'=>$data['tmus'],
	    'message'=>'<b>(US)</b> CPU  max :'.$us_max,
	    'yaxis'=> 'max<70'
	    ];
	$sy = [
	    'data'=>$data['tmsy'],
	    'message'=>'<b>(SY)</b> CPU  max :'.$sy_max,
	    'yaxis'=> 'max<35'
	    ];	
	
	$b = [
	    'data'=>$data['tmb'],
	    'message'=>'<b>(B)</b> HDD  avrg :'.$b_average,
	    'yaxis'=> 'avrg < '.$amtHDD,
	    ];	
	

	
	return $this->message(true, $filetime, ['si' => $si, 'us' => $us, 'sy' => $sy, 'b' => $b]);
    }

    public function chartRam() {
	$mes0 = shell_exec("free -g");
	$mes = '<pre>'.$mes0."</pre>";

	$page = explode("\n", $mes0);
	$memory = preg_split("/[\s]+/", $page[1]);
	$swap = preg_split("/[\s]+/", $page[2]);
	$procm = shell_exec("ps aux | awk '{s += $4} END {print s }'");
	$data = [];
	$data[] = [
	    'name' => 'Использовано',
	    'y' => floatval($procm),
	    'color' => 'black',
	];
	$data[] = [
	    'name' => 'Свободно',
	    'y' => 100 - floatval($procm),
	    'color' => 'green',
	];

	return $this->message(true, $mes, $data);
    }

    public function chartProcRam() {
	$mes =shell_exec('ps -eo cmd,%mem --sort=-%mem | head');
	$page = explode("\n", shell_exec("free -g"));
	$memory = preg_split("/[\s]+/", $page[1])[1] * 1024;

	$ps = "ps axo rss,comm,pid  | awk '{ proc_list[$2]++; proc_list[$2 " .
		'","' . " 1] += $1; } END { for (proc in proc_list) { printf(" .
		'"%d\t%s\n"' . ", proc_list[proc " . '","' . " 1],proc); }}' ";
	$ps .= " | sort -n | tail -n 10 | sort -rn ";
	$ps .= " | awk '{" . '$' . "1/=1024;printf " . '"%.0f\t"' . ',$1}{print $2}' . "'";
	
	$umem = explode("\n", shell_exec($ps));

	$mem = shell_exec("ps aux | awk '{s += $4} END {print s }'");
	$data = [];
	$all = 0;
	array_pop($umem);
	foreach ($umem as $value) {
	    $tmp = preg_split("/[\t]/", $value);
	    $vl = floatval($tmp[0]) * 100 / $memory;
	    if ($vl > 1) {
		$all += floatval($tmp[0]) * 100 / $memory;

		$data[] = [
		    'name' => $tmp[1],
		    'y' => round(floatval($tmp[0]) * 100 / $memory, 1),
		];
	    }
	}

	$data[] = [
	    'name' => 'Другие',
	    'y' => round($mem - $all, 1),
	];
	return $this->message(true, $mes, ['series'=>$data,'message'=>$mem.'%']);
    }

    public function chartProcCpu() {
	$cpuall = floatval(shell_exec("nproc"));

	$allcpu = shell_exec("ps aux | awk '{s += $3} END {print s }'");
	$mes =shell_exec('ps -eo cmd,%cpu --sort=-%cpu | head');
	$umem = explode("\n", $mes);
	array_shift($umem);
	array_pop($umem);
	$data = [];
	$all = 0;
	foreach ($umem as $value) {
	    $tmp = preg_split("/[\s]+/", $value);

	    $lastCpu = floatval(array_pop($tmp)) * 100 / floatval($allcpu);
	    $text = implode(' ', $tmp);

	    //if ($lastCpu > 1) {
	    $all += $lastCpu;
	    $data[] = [
		'name' => $text,
		'y' => round(floatval($lastCpu), 1),
	    ];
	    // }
	}
	$data[] = [
	    'name' => 'Другие',
	    'y' => round(100 - $all, 1),
	];

	return $this->message(true, $mes, $data);
	//return $this->message(true, 'CPU занят на '.$all.'  ' . (round(floatval($allcpu) * 100 / $cpuall/10, 2, 0)) . ' %', $data);
    }

    public function chartCPU() {
	$cores = shell_exec("nproc");
        $core = '<pre>'.shell_exec("inxi -C -c 0")."</pre>";
        
//	$core = explode("\n", shell_exec("inxi -C -c 0"));
//	$core = implode("<br>",preg_split("/[:\s]/i", $core));
        
        $lavrg =  shell_exec("uptime");
        $core .="<b>".$lavrg."</b>";
	$loadover = preg_split("/load average:/", $lavrg);
	$loadover = preg_split("/[,]/", $loadover[1]);

	$data = [
	    ['name' => 'Всего ядер', 'y' => floatval($cores), 'color' => '#72b3ec'],
	    ['name' => 'сейчас', 'y' => floatval($loadover[0]), 'color' => floatval($loadover[0]) < floatval($cores) - 1 ? '#72eca8' : '#f1724c'],
	    ['name' => '5 минут', 'y' => floatval($loadover[1]), 'color' => floatval($loadover[0]) < floatval($cores) - 1 ? '#72eca8' : '#f1724c'],
	    ['name' => '15 минут', 'y' => floatval($loadover[2]), 'color' => floatval($loadover[0]) < floatval($cores) - 1 ? '#72eca8' : '#f1724c'],
	];

	return $this->message(true, $core, $data);
    }

    public function chartHDD() {
	$ddisk = shell_exec("inxi -D -c 0");
	//ID-1: /dev/sda vendor: Intel model: SSDSC2KB240G7 size: 223.57 GiB
	$tmp = preg_split("/\h+|(\W:)/", $ddisk);
	$r_data = [];
	$subtitle = '';
	//return json_encode($tmp);
	$szrd = sizeof($tmp);

	for ($i = 0; $i < $szrd; $i++) {
	    if (mb_strpos($tmp[$i], 'ID-') !== false) {

		if (isset($rd) && sizeof($rd)) {
		    $r_data[] = $rd;
		    $subtitle .= '<br>';
		}

		$rd = [];

		$subtitle .= $tmp[$i + 1];
		$rd['name'] = $tmp[$i + 1];


		$i++;
	    } elseif (mb_strpos($tmp[$i], 'vendor:') !== false) {
		$subtitle .= ' ' . $tmp[$i + 1];
		$i++;
	    } elseif (mb_strpos($tmp[$i], 'model:') !== false) {
		//$subtitle .=' '.$tmp[$i + 1];
		$i++;
	    } elseif (mb_strpos($tmp[$i], 'size:') !== false) {
		$rd['y'] = floatval($tmp[$i + 1]);

		$subtitle .= ' ' . $tmp[$i + 1] . ' GB';
		$i++;
	    }
	}
	$r_data[] = $rd;

	return $this->message(true, $ddisk, $r_data);
    }

    public function chartRAID() {

	$subtitle = '';
	$sub = [];
	$r_data = [];
	$mes = shell_exec("cat /proc/mdstat");
	$raid = explode("\n", $mes);
	array_shift($raid);
	$dev = [];
	$szr = sizeof($raid);
	$massiv = [];
	for ($i = 0; $i < $szr; $i++) {
	    if (mb_strpos($raid[$i], ' : ') !== false) {
		if (isset($rd) && sizeof($rd)) {
		    $r_data[] = $rd;
		    $sub[$rd['name']] = $sb;
		}
		$rd = [];
		$sb = [];
		$tmp = explode(':', $raid[$i]);
		$rd['name'] = trim($tmp[0]);
		$rd['drilldown'] = trim($tmp[0]);


		$tmp = preg_split("/[\s]+/", trim($tmp[1]));

		$rd['color'] = $tmp[0] == 'active' ? '#64d471' : '#e8bd5c';
		$m2 = array_pop($tmp);
		$m1 = array_pop($tmp);
	    } elseif (mb_strpos($raid[$i], 'blocks') !== false) {
		$tmp = preg_split("/[\s]+/", trim($raid[$i]));
		$rd['y'] = $tmp[0] / 1024 / 1024;

		$ww = str_replace(['[', ']'], '', array_pop($tmp));

		$sb[] = [
		    'name' => $m1,
		    'y' => $rd['y'],
		    'color' => $ww[0] == 'U' ? '#64d471' : '#e8bd5c'
		];
		$sb[] = [
		    'name' => $m2,
		    'y' => $rd['y'],
		    'color' => $ww[1] == 'U' ? '#64d471' : '#e8bd5c'
		];
	    }
	}
	$r_data[] = $rd;
	$sub[$rd['name']] = $sb;

	return $this->message(true, implode('<br>',$raid), ['sub' => $sub, 'series' => $r_data]);
    }

    public function chartRAID_1() {
	$raid = explode("\n", shell_exec('./arcconf getconfig 1 pd|egrep "     State\>|S.M.A.R.T. warnings"')); //Device #|State\>|Reported Location|Reported Channel|S.M.A.R.T. warnings

	$subtitle = '';
	$sub = [];
	$r_data = [];
	//$raid = explode("\n", shell_exec("cat /proc/mdstat"));
	//array_shift($raid);
	$dev = [];
	array_pop($raid);
	$szr = sizeof($raid);
	$massiv = [];
	$err = false;
	$f = 1;


	for ($i = 0; $i < $szr; $i++) {


	    $dev = explode(':', $raid[$i]);
	    $err = mb_strpos($dev[1], 'Online') === false ? false : true;
	    $i++;
	    $dev = explode(':', $raid[$i]);
	    $err = $dev[1] == 0 ? false : true;
	    $sb[] = [
		'name' => 'Dev' . $f,
		'y' => 6000,
		'color' => !$err ? '#64d471' : '#e8bd5c'
	    ];
	    $f++;
	}


	$r_data[] = [
	    'name' => 'md0',
	    'drilldown' => 'md0',
	    'y' => 6000,
	    'color' => !$err ? '#64d471' : '#e8bd5c'
	];

	$sub['md0'] = $sb;


	return $this->message(true, $subtitle, ['sub' => $sub, 'series' => $r_data]);
    }

    public function MachineFeatures() {
        
     	return $this->message(true, file_get_contents('machine.txt'));   
    }
    public function chartProcHDD() {
	$df = explode("\n", shell_exec('df -P'));
	array_shift($df);
	array_pop($df);
	//print_r($df);exit;
	$df_data = [];
	$us = [];
	$free = [];
	$mnt = [];
	foreach ($df as $row) {
	    list($fs, $size, $usedd, $freed, $procd, $mntd) = preg_split("/[\s]+/", $row);

	    $us = intval(str_replace('%', '', $procd));
	    $used[] = ["y" => $us, "zname" => $fs];
	    $free[] = ["y" => 100 - $us, "zname" => $fs];
	    $mnt[] = $mntd;
	}

	return $this->message(true, '$subtitle', ['categoryes' => $mnt, 'series' => [$free, $used]]);
    }

    public function chartDuHDD() {
	$du = explode("\n", shell_exec("du -shm --exclude='proc' /*| sort -nr"));
	array_pop($du);
	$du_data = [];
	foreach ($du as $row) {
	    list($size, $mntd) = preg_split("/[\s]+/", $row);
	    $du_data[] = [
		'y' => floatval($size) / 1024,
		'name' => $mntd,
	    ];
	}
	return $this->message(true, '$subtitle', $du_data);
    }

    private function getintval($param) {
	switch (substr($param, strlen($param) - 1, 1)) {
	    case 'M':
		return intval($param);
		break;
	    case 'K':
		return intval($param) / 1024;
		break;

	    default:
		return intval($param) / 1024 / 1024;
	}
    }

    public function chartEth0() {

	$iface = 'enp3s0';
	$itime = 60;
	//return shell_exec("ifstat -s ");
	$inet = shell_exec("ifstat -s  --interval=60 | awk '$1==\"" . $iface . "\" {print $6, $8}'");
	$inet = preg_split("/[\s]+/", $inet);
	array_pop($inet);
	//return json_encode($inet);

	$data[] = [
	    'name' => 'Прием',
	    'color' => '#64d471',
	    'y' => $this->getintval($inet[0])
	];
	$data[] = [
	    'name' => 'Передача',
	    'color' => 'black',
	    'y' => $this->getintval($inet[1])
	];



	return $this->message(true, '$subtitle', $data);
    }

    public function chartEth0_1() {
	//https://its.1c.eu/db/metod8dev#content:5824:hdoc
	//мониторинг сети и железа
	$iface = 'enp3s0';

	shell_exec("bwm-ng -o csv -c 6 -T rate -I enp3s0 > /var/www/html/dashboard/bandwidth.csv");
	/*
	  0 unix timestamp
	  1 interface
	  2 bytes_out/s
	  3 bytes_in/s
	  4 bytes_total/s
	  5 bytes_in
	  6 bytes_out
	  7 packets_out/s
	  8 packets_in/s
	  9 packets_total/s
	  10 packets_in
	  11 packets_out
	  12 errors_out/s
	  13 errors_in/s
	  14 errors_in
	  15 errors_out
	 */
	$sum = [
	    'bytes_out' => 0,
	    'bytes_in' => 0,
	    'packets_out' => 0,
	    'packets_in' => 0,
	    'errors_out' => 0,
	    'errors_in' => 0,
	];

	if (($handle = fopen("bandwidth.csv", "r")) !== FALSE) {
	    while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
		$sum['bytes_out'] += $data[2];
		$sum['bytes_in'] += $data[3];
		$sum['errors_out'] += $data[12];
		$sum['errors_in'] += $data[13];
	    }
	    fclose($handle);
	}

	//return $this->message(true, '$subtitle', $sum);


	$data[] = [
	    'name' => 'Прием',
	    'color' => !$sum['errors_in'] ? '#64d471' : '#e8bd5c',
	    'y' => $sum['bytes_in'] / 1024,
	];
	$data[] = [
	    'name' => 'Передача',
	    'color' => !$sum['errors_out'] ? '#64d471' : '#e8bd5c',
	    'y' => $sum['bytes_out'] / 1024
	];




	return $this->message(true, '$subtitle', $data);
    }

    public function getSystem() {
	/**
	 * ps aux | awk '{s += $3} END {print s "%"}' в процентах СPU $4 в % память
	  -C процессор
	  -B батарея
	  -D диски
	  -i сетевые карты
	  -I инфо
	 * 
	 *    shell_exec("inxi -CMDR -c 0")  */
	//ob_start();
	$page = explode("\n", shell_exec("free -g"));
	$memory = preg_split("/[\s]+/", $page[1]);
	$swap = preg_split("/[\s]+/", $page[2]);
	$procm = shell_exec("ps aux | awk '{s += $4} END {print s }'");

	// ob_end_clean();
	$procm = shell_exec("ps aux | awk '{s += $4} END {print s }'");
	$procc = shell_exec("ps aux | awk '{s += $3} END {print s }'");
	$umem = explode("\n", shell_exec('ps -eo pid,ppid,cmd,%mem,%cpu --sort=-%mem | head'));
	$ucpu = explode("\n", shell_exec(' ps -eo pid,ppid,cmd,%mem,%cpu --sort=-%cpu | head'));



	$cores = shell_exec("nproc");
	$core = explode("\n", shell_exec("inxi -C -c 0"));
	$core = preg_split("/[:]/i", $core[0]);
	$loadover = preg_split("/load average:/", shell_exec("uptime"));
	$loadover = preg_split("/[,]/", $loadover[1]);

	$df = explode("\n", shell_exec('df -m'));
	array_shift($df);
	array_pop($df);
	$df_data = [];
	foreach ($df as $row) {
	    list($fs, $size, $usedd, $freed, $procd, $mntd) = preg_split("/[\s]+/", $row);
	    $df_data[] = [
		'fs' => $fs,
		'sz' => $size,
		'used' => $usedd,
		'free' => $freed,
		'proc' => $procd,
		'mnt' => $mntd,
	    ];
	}
	$du = explode("\n", shell_exec("du -shm --exclude='proc' /home/*| sort -nr"));
	array_pop($du);
	foreach ($du as $row) {
	    list($size, $mntd) = preg_split("/[\s]+/", $row);
	    $du_data[] = [
		'sz' => $size,
		'mnt' => $mntd,
	    ];
	}

	$ddisk = explode("\n", shell_exec("inxi -D -c 0"));
	array_shift($ddisk);
	array_pop($ddisk);

	/* 	
	  foreach ($ddisk as $row) {

	  return preg_split("/\h+|(\W:)/", $row);
	  }
	 */
	//$raid = explode("\n", shell_exec("inxi -R -c 0"));
	$raid = shell_exec("inxi -R -c 0");
	$tmp = preg_split("/\h+|(\W:)/", $raid);
	$r_data = [];

//return $tmp;
	$szrd = sizeof($tmp);
	for ($i = 0; $i < $szrd; $i++) {
	    if (mb_strpos($tmp[$i], 'Device') !== false) {

		if (isset($rd) && sizeof($rd))
		    $r_data[] = $rd;

		$rd = [];
		$rd['dev'] = $tmp[$i + 1];
		$i++;
	    }
	    elseif (mb_strpos($tmp[$i], 'type:') !== false) {
		$rd['type'] = $tmp[$i + 1];
		$i++;
	    } elseif (mb_strpos($tmp[$i], 'status:') !== false) {
		$rd['status'] = $tmp[$i + 1];
		$i++;
	    } elseif (mb_strpos($tmp[$i], 'raid:') !== false) {
		$rd['raid'] = $tmp[$i + 1];
		$i++;
	    } elseif (mb_strpos($tmp[$i], 'raid:') !== false) {
		$rd['raid'] = $tmp[$i + 1];
		$i++;
	    } elseif (mb_strpos($tmp[$i], 'report:') !== false) {
		$rd['report'] = [
		    'on' => $tmp[$i + 1],
		    'status' => $tmp[$i + 2],
		];
		$i++;
		$i++;
	    } elseif (mb_strpos($tmp[$i], 'Components:') !== false) {
		$rd['Components'] = [
		    'status' => $tmp[$i + 2],
		    'dev' => [$tmp[$i + 3], $tmp[$i + 4]]
		];
		$i++;
	    }
	}

	$iface = 'enp1s0f0';
	$itime = 60;
	$inet = shell_exec("ifstat -s --interval=" . $itime . " | awk '$1==\"" . $iface . "\" {print $6, $8}'");
	$inet = preg_split("/[\s]+/", $inet);

	//return $r_data;
	//du -shm --threshold=102400 --exclude='proc' /*| sort -nr //в мегабайтах
	//ifstat -s --interval=360 | awk '$1=="enp1s0f0" {print $6, $8}' //средняя за час и не обновлять историю


	$return = [
	    'ram' => [
		'all' => $memory[1],
		'used' => $memory[2],
		'proc_used' => $procm,
	    ],
	    'swap' => [
		'all' => $swap[1],
		'used' => $swap[2],
		'proc_used' => $swap[2] ? $swap[2] * 100 / $swap[1] : 0,
	    ],
	    'cpu' => [
		'core' => str_replace('bits', '', $core[3]),
		'cores' => $cores,
		'load' => $loadover,
		'proc_used' => $procc,
	    ],
	    'used_mem' => $umem,
	    'used_cpu' => $ucpu,
	    'df' => $df_data,
	    'du' => $du_data,
	    'disks' => $ddisk,
	    'raid' => $r_data,
	    'inet' => [
		'iface' => $iface,
		'time' => $itime,
		'rx' => $inet[0],
		'tx' => $inet[1],
	    ]
	];
	//  ob_get_contents();


	return $return;
    }

    public function grep($param) {
	$fpath = $_SERVER['DOCUMENT_ROOT'];
	if (isset($param['path']) && $param['path']) {
	    $fpath .= "/" . $param['path'];
	}

	$d = dir($fpath);

	$arrayFiles = [];
	while (false !== ($entry = $d->read())) {
	    if ($param['path']) {
		preg_match('/\./', $entry, $matches, PREG_OFFSET_CAPTURE);
	    } else {
		preg_match('/docum|\.|_pay|uploads|forum|sxd_optochip_org|sitemaps|cgi-bin/', $entry, $matches, PREG_OFFSET_CAPTURE);
	    }

	    if ($matches) {
		continue;
	    }

	    $arrayFiles[] = $entry;
	}

	$d->close();

	$res = '';

	foreach ($arrayFiles as $file) {
	    $find = shell_exec("grep -i -R -n -F \"" . str_replace("\"", "\\\"", $param['term']) . "\" " . $fpath . "/" . $file . "/");
	    if ($find) {
		$rows = htmlentities($find);
		$rows = str_replace($fpath . "/" . $file, "", $rows);
		$rows = trim($rows);
		$rows = explode("\n", $rows);

		$oldFile = '';
		$result = [];

		foreach ($rows as $k => $row) {
		    $t = explode(":", $row, 3);

		    if (is_array($t) && count($t) > 2) {
			$f = $t[0];
			if ($oldFile !== $f) {
			    $result[] = '<span class="text-red"><strong>' . $f . '</strong></span>';
			    $oldFile = $f;
			}

			if (strpos($t[2], '//') || strpos($t[2], '/*') || strpos($t[2], '*/')) { // если комментарий
			    $result[] = "&nbsp;&nbsp;<span class='text-success'><strong>" . $t[1] . "</strong></span>: <span class='text-success'>" . trim($t[2]) . "</span>";
			} else {
			    $result[] = "&nbsp;&nbsp;<span class='text-success'><strong>" . $t[1] . "</strong></span>: " . trim($t[2]);
			}
		    }
		}

		$result = implode("\n", $result);
		$res .= '<h4>' . $file . '</h4><pre>' . $result . '</pre>';
	    }
	}

	return $this->message(true, $res);
    }

}

$i = new Dashboard();

if (!$q = filter_input(INPUT_POST, "command"))
    echo $i->message(false);

if (method_exists($i, $q)) {
    echo $i->$q();
} else
    echo $i->message(false);
?>