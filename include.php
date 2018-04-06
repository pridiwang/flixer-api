<?php 

require "config.php";

$db=mysqli_connect($dbserver,$dbuser,$dbpwd,$dbuser) or die ('can not connect $dbserver > $dbuser ');
$db->query('set names utf8');
global $db,$json;
extract($_GET);
if($action=='delete'){
	$q="delete from  $tb where id='$id' ";
	qexe($q);
	$action='browse';
}
if($action=='update'){
	if($id){
		$q="update $tb set logs=concat(now(), ' updated by $_SESSON[user]\n',logs) ";
		while(list($fld,$val)=each($_POST)){
			$val=addslashes($val);
			if(($fld=='password')&&($val)) $q.=", $fld=password('$val') ";
			else $q.=", $fld='$val' ";
		}
		$q.=" where id='$id' ";
	}else{
		$vars=" logs ";
		$vals=" concat(now(),' added by $_SESSION[user]') ";
		while(list($fld,$val)=each($_POST)){
			$val=addslashes($val);
			$vars.=" ,$fld ";
			if(($fld=='password')&&($val)) $vals.=" ,password('$val') ";
			else $vals.=", '$val' ";
		}
		$q="insert into $tb ( $vars ) values ( $vals ) ";
	}
	qexe($q); //print $q;
	if(!$id) $id=$db->insert_id;
	$action='browse';
	$title=$_POST[title];
	if($tb=='title') qexe("update $tb set updated=now() where id='$id' ");
	if($tb=='episode'){
		$id=qval("select title from $tb where id='$id' ");
		$action='edit';$tb='title';
	}
}
function epsetstatus($id){
	$q="update episode set status='publish' where title='$id' and status='draft' and publish_time between '2018-01-01' and now()  ";
	qexe($q);
	$q="update episode set status='draft' where title='$id' and status='publish' and hold_time between '2018-01-01' and now()  ";
	qexe($q);
	$q="update episode set status='draft' where title='$id' and status='publish' and publish_time > now()  ";
	qexe($q);
	$published=qcount("select id from episode where title='$id' and status='publish' ");
	if($published==0) qexe(" update title set status='draft' where id='$id' ");
	
}

function error($errcode){
	global $json,$lang;
	$tfld='title_'.$lang;
	$mfld='message_'.$lang;
	$bfld='button_'.$lang;
	$dr=qdr("select $tfld as 'title',$mfld as 'message',$bfld as 'button' from error_code where code='$errcode' ");
	$json[message]=$dr;
	
	
	
}
function ep2rename($code){
	$code1=str_replace('2','',$code);
	$dir="/data/dex/$code";
	//$code1='SAINT';
	print" rename ep2 $dir from $code1 -> $code ";
	$dh2=opendir($dir);
	while($file=readdir($dh2)){
		if($file=='.') continue;
		if($file=='..') continue;
		$file2=$file;
		$file2=str_replace($code1.'_',$code.'_',$file2);
		//print "<br> rename $dir/$file -.$dir/$file2 \n";
		$rs=rename("$dir/$file","$dir/$file2");
	}
	closedir($dh2);
}
function mp4rename($d2){
	print "dir $d2 <br>\n";
	$dh2=opendir($d2);
	while($file=readdir($dh2)){
		if($file=='.') continue;
		if($file=='..') continue;
		$file2=$file;
		$file2=str_replace('_00','_0',$file2);		
		$file2=str_replace('_010_','_10_',$file2);		
		$file2=str_replace('_011_','_11_',$file2);		
		$file2=str_replace('_ED_','_',$file2);
		$file2=str_replace('_EP01_','_',$file2);
		$file2=str_replace('_EP02_','_',$file2);
		$file2=str_replace('_LC02_','_',$file2);
		$file2=str_replace('_TH1000','_480_th',$file2);
		$file2=str_replace('_JP1000','_480_jp',$file2);
		$file2=str_replace('_EP','_',$file2);
		//print " - $d2/$file -> $d2/$file2 <br>\n";
		$rs=rename("$d2/$file","$d2/$file2");
		//print " -- renamed ";
	}
	closedir($dh2);
}
function txt2array($dr){
	
	global 	$vwrating;
	if(array_key_exists('rating',$dr)){
		$vr=$dr['rating'];
		$dr['rating']= $vwrating[$vr];
	} 
	if(array_key_exists('score',$dr)){
	//	$dr['score']=rand(0,10)/2;
		//$dr['score']=5;
		
	}
	//;
	

	$flds=array('audio'=>'language','subtitle'=>'language','subtitles'=>'language','resolutions'=>'quality','tags'=>'word');
	while(list($k,$v)=each($flds)){
		if(array_key_exists($k,$dr)){
			$arr=explode(',',$dr[$k]);
			unset($dr[$k]);
			while(list(,$val)=each($arr)){
				//print "val $val<br>";
				$dr[$k][]=array($v=>$val);
			}
		}
	}
	
	return $dr;
}

function fnameclean($in){
	$out=$in;
	$out=str_replace(' ','_',$out);
	$out=str_replace('(','',$out);
	$out=str_replace(')','',$out);
	return $out;
}
function sendFCM($mess) {
	global $json;
	$id="f8oMeZExGJM:APA91bF8jn-GE3RL2Bjdb41bwXuFvo7IlVgynSTGUPOqgLfDPG_hUbrKPHsSFIWdgBLw7i2hzEC5uCqUEPIQjQSE2qQJdpoL0Asb2NDcLgIVzqiO6Mc9fSXv_FXgk-0cPS-1ogeJipuv";
	$url = 'https://fcm.googleapis.com/fcm/send';
	$fields = array (
			'to' => $id,
			'notification' => array (
					"body" => $mess,
					"title" => "บทความใหม่"
			)
	);
	$fields = json_encode ( $fields );
	$headers = array (
			'Authorization:key=' . "AIzaSyBg81u7F8HU33bjUnmb-PgExAV0xzCJ1SQ",
			'Content-Type:application/json'
	);

	$ch = curl_init ();
	curl_setopt ( $ch, CURLOPT_URL, $url );
	curl_setopt ( $ch, CURLOPT_POST, true );
	curl_setopt ( $ch, CURLOPT_HTTPHEADER, $headers );
	curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
	curl_setopt ( $ch, CURLOPT_POSTFIELDS, $fields );

	$result = curl_exec ( $ch );
	$json[error]=curl_error($ch);
	curl_close ( $ch );
	$json[result]=$result;
	
}

function qdata($q){
	global $json;
	$dt=array();
	$dt=qdt($q);
	if(count($dt)==0){
		$dt=array();
	}
	while(list($i,$dr)=each($dt)){
	}
	$json[response]=true;
	$json[result]=$dt;	
}
function qdata0($q){
	global $json;
	$dt=array();
	$dt=qdt($q);
	if(count($dt)==0){
		$dt=array();
	}
	while(list($i,$dr)=each($dt)){
		
		$pid=$dr[id];
		$q="select t2.name 'name',t2.term_id 'id' from wp_term_relationships as t1,wp_terms as t2 where t2.term_id=t1.term_taxonomy_id and t1.object_id='$pid' and term_order=0 ";
		//$dt[$i][q]=$q;
		$cat=qdr($q);		
		$dt[$i][category]=$cat[name];
		$dt[$i][cat_id]=$cat[id];
		
		$imgid=qval("select id from wp_posts where post_parent='$pid' and post_mime_type in ('image/jpeg','image/png') ");
		$metajson=qval("select meta_value from wp_postmeta where post_id='$imgid' and meta_key='_wp_attachment_metadata' ");
		//print $metajson;
		$meta=unserialize($metajson);
		$img='http://cafe.itban.com/wp-content/uploads/'.$dr[yrmo].'/'.$meta[sizes][medium][file];
		$dt[$i][img]=$img;
		$ct=$dr[content];
		$dt[$i][eb]='';
		$dt[$i][yt]='';
		$dt[$i][mp4]='';
		$dt[$i][vdo]='';
		if(strpos($ct,'[/embed]')){
			$e1=strpos($ct,'[embed]');
			if(!$e1) $e1=0;
			$e2=strpos($ct,'[/embed]');
			$eb=substr($ct,$e1+7,$e2-$e1-7);
			
			$yt=str_replace('https://www.youtube.com/watch?v=','',$eb);
			if(strpos($eb,'youtu.be')) $yt=str_replace('https://youtu.be/','',$eb);
			
			
			$dt[$i][eb]=$eb;
			$dt[$i][yt]=$yt;
			if(strpos($eb,'.mp4')){
				$dt[$i][mp4]=$eb;
				$dt[$i][yt]='';
			}
			if((!$img)&&($yt)){
				$dt[$i][img]="https://img.youtube.com/vi/$yt/0.jpg";
			}
			$vdo="<iframe style=margin:10px; width=100% src=https://www.youtube.com/embed/$yt?rel=0&amp;controls=0&amp;showinfo=0 frameborder=0 allowfullscreen></iframe>";
			$dt[$i][vdo]=$vdo;
			
			//$dt[$i][content]=str_replace("[embed]","<embed>",$$dt[$i][content]);
			//$dt[$i][content]=str_replace("[/embed]","</embed>",$$dt[$i][content]);
			//$dt[$i][content].=$vdo;
		
		}
		if($dr2[img]){
			$ext=substr($dr2[img],strlen($dr2[img])-3,3);
			$img2="media/$dr[id].$ext";
			$dt[$i][img2]=$img2;
			copy($dr2[img],$img2);
		}
		$dr2=$dt[$i];
		$q="insert into posts (id,category,date,title,content,image,vdo,youtube) values 
		('$dr2[id]','$dr2[cat_id]','$dr2[date]','$dr2[title]','$dr2[content]','$img2','$dr2[mp4]','$dr2[yt]' ) ";
		//$db2->query($q);
	}
	$json[response]=true;
	$json[result]=$dt;	
}

function medialib(){
	$dir="media";
	$dh=opendir($dir);
	$q="select name 'file',type 'ext' from media order by datetime desc ";
	$dt=qdt($q);
	while(list($i,$dr)=each($dt)){
		extract($dr);
		$url='http://'.$_SERVER[SERVER_NAME]."/$dir/".$file;
		$mview="<img src=$url height=90  alt=$url title=$url>";
		if($ext=='mp4') {$mtype='vdo'; $mview='<img src=../img/clip.jpg height=90  alt=$file title=$file>';}
		else $mtype='image';
		$item="<div class='media-item $mtype' url=$url >$mview<div class=media-file>$file</div></div>";
		$out.=$item;
	}
	return $out;
}
function qdt($q){
	global $db,$json;
	$ck=$db->query($q);
	if(!$ck){
		$json[error]=$db->error;
		return false;
	}
	while($dr=$ck->fetch_assoc()){
		$dt[]=$dr;
	}
	return $dt;
}
function qdr($q){
	global $db,$json;
	$ck=$db->query($q);
	if(!$ck){
		$json[error]=$db->error;
		return false;
	}
	$dr=$ck->fetch_assoc();
	return $dr;
}
function qcount($q){
	global $db,$json;
	$ck=$db->query($q);
	if(!$ck){
		$json[error]=$db->error;
		return 0;
	}
	
	$o=$ck->num_rows;
	return $o;
}
function qval($q){
	global $db,$json;
	$ck=$db->query($q);
	if(!$ck){
		$json[error]=$db->error;
		return false;
	}
	$dr=$ck->fetch_array();
	return $dr[0];
}
function qexe($q){
	global $db,$json;
	$ck=$db->query($q);
	if(!$ck){
		$json[error]=$db->error;
		print $db->error;
		return false;
	}
	return $ck;
}
function qoptions($q,$val){
	$dt=qdt($q);
	
	while(list(,$dr)=each($dt)){
		if($dr[id]==$val) $out.="<option selected value=$dr[id]>$dr[name]";
		else $out.="<option value=$dr[id]>$dr[name]";
	}
	return $out;
}
function qbrowse($q,$tb){
	global $controlflds,$sumflds,$countflds;
	$maxlen=200;
	$dt=qdt($q); //print $q;
	if(count($dt)==0) print $q;
	$rnd=rand(0,9999);
	while(list($i,$dr)=each($dt)){
		$k=$i+1;
		$tbody.= "<tr onclick=window.location.href='?action=edit&tb=$tb&id=$dr[id]'><td>$k</td>";
		while(list($fld,$val)=each($dr)){
			if(in_array($fld,$controlflds)) continue;
			if(in_array($fld,$sumflds))$sum[$fld]+=$val;
			if(in_array($fld,$countflds)&&($val<>'')) $sum[$fld]+=1;
			if($i==0) $thead.="<td>$fld</td>";
			if($val=='0000-00-00') $val='';
			if($val=='0000-00-00 00:00:00') $val='';
			if($fld=='tags') $val=str_replace(',',', ',$val);
			
			if($val=='publish') $val="<i class='btn btn-success'></i>";
			if($val=='draft') $val="<i class='btn btn-danger'></i>";
			if(strlen($val)>$maxlen) $val=substr($val,0,$maxlen).'..';
			if(substr($val,0,5)=='f_chk') $val=f_val($val);
			$tbody.= "<td class='$fld'>$val</td>";
		}
		//$link="<a href=?action=edit&tb=$tb&id=$dr[id]>Edit</a> | ";
		//if($tb=='title') $link="<a href=?action=ep&title=$dr[id]>EP</a> | <a href=?action=img&dir=img/dex/$dr[code]>Img</a> 		<a href=img/dex/$dr[code]/poster.png?$rnd>P</a> | 		<a href=img/dex/$dr[code]/banner.png?$rnd>B</a>		";
		if($tb=='title') $link="<a href=?action=img&dir=img/dex/$dr[code]><i class='fa fa-picture-o'></i></a>";
		$tbody.= "<td>$link</td></tr>";
	}
	
	reset($dt);
	list(,$dr)=each($dt);
	while(list($fld,)=each($dr)){
		if(in_array($fld,$controlflds)) continue;
		$val='';
		if(in_array($fld,$sumflds)) $val=$sum[$fld];
		if(in_array($fld,$countflds)) $val=$sum[$fld];
		$tfoot.="<td class='$fld'>$val</td>";
	}
	$out="<table class='table table-bordered'>
	<thead><tr><td>No.</td>$thead<td></td></tr></thead>
	<tbody>$tbody</tbody>
	<tfoot><tr><td></td>$tfoot<td></td></tr></tfoot>
	</table>";
	return $out;
}
function f_val($in){
	//return '';
	
	$d=explode('-',$in);
	$o='';
	//$o=$d[0];
	if($d[0]=='f_chk'){
		$title=$d[1];
		$code=qval("select code from title where id='$title' ");
		$ep=$d[2];
		
		$img="img/dex/$code/$ep.png";
		//$o=$img;
		if(file_exists($img)) $o='ok';
		else $o='';
		
	}
	return $o;
	
}
function encrypt_string($string = '', $salt 		= '8638FD63E6CC16872ACDED6CE49E5A270ECDE1B3B938B590E547138BB7F120EA') {
	$key = pack('H*', $salt);    
    $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
    $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
    $ciphertext = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $string, MCRYPT_MODE_CBC, $iv);
    return base64_encode($iv . $ciphertext);
}

function decrypt_string($encodedText = '', $salt	= '8638FD63E6CC16872ACDED6CE49E5A270ECDE1B3B938B590E547138BB7F120EA') {
	$key = pack('H*', $salt);
    $ciphertext_dec = base64_decode($encodedText);
    $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
    $iv_dec = substr($ciphertext_dec, 0, $iv_size);
    $ciphertext_dec = substr($ciphertext_dec, $iv_size);
    return mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, $ciphertext_dec, MCRYPT_MODE_CBC, $iv_dec);
}
function encrypt($data){
	global $encryptkey;
	require 'vendor/autoload.php';
	$cryptor = new \RNCryptor\Encryptor();
	$encrypted = $cryptor->encrypt($data, $encryptkey);
	return $encrypted;
}
function decrypt($data){
	global $encryptkey;
	require 'vendor/autoload.php';
	$decryptor = new \RNCryptor\Decryptor();
	$decrypted = $decryptor->decrypt($data, $encryptkey);
	if(!$decrypted) return '';
	return $decrypted;
}


?>
