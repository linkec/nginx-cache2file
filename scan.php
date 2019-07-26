<?php
date_default_timezone_set('Asia/Shanghai');
listDir("/home/cache");
function listDir($dir){
	if(is_dir($dir)){
		if ($dh = opendir($dir)) {
			while (($file = readdir($dh)) !== false){
				$_file = $dir."/".$file;//绝对文件位置
				if($file!="." && $file!=".."){
					if(is_dir($_file)){
						listDir($dir."/".$file);
					}else{
                        processCache($_file);
					}
				}
			}
			closedir($dh);
		}
    }
}
function processCache($file){
    if(filesize($file)<60){
        return;
    }
    $fh = fopen($file,'rb');
    if(!$fh){
        return;
    }
    $meta = getCacheHeader($fh,$file);
    //skip to next line;
    fgets($fh);
    //key line
    $_key = fgets($fh);
    list($_,$key) = explode(":",$_key,2);
    $key = trim($key);
    writeOrigin('/home/rebuild',$key,$meta,$fh);
    fclose($fh);
    // echo $file.date('Y-m-d H:i:s',fileatime($file)).PHP_EOL;
}
function writeOrigin($dir,$path,$meta,$fh){
    $baseDir = $dir.dirname($path);
    $baseName = basename($path);
    if(substr($baseName,-3,3)!='.ts'){
        return;
    }
    if(file_exists($baseDir)){
        if(!is_dir($baseDir)){
            var_dump("$baseDir is not a folder.");
            return;
        }
    }else{
        make_dir($baseDir);
    }
    if(file_exists($baseDir.'/'.$baseName)){
        var_dump("$baseDir$baseName is exist.");
        return;
    }else{
        $fn = fopen($baseDir.'/'.$baseName,'wb');
        fseek($fh,$meta['body_start']);
        while(!feof($fh)){
            fwrite($fn,fread($fh,8192));
        }
        echo $baseDir.'/'.$baseName.' Done.'.PHP_EOL;
        fclose($fn);
    }
}
function getCacheHeader($fh,$file=''){
    $t = fread($fh,58);
    $rs = unpack("Lversion/L_/Lvalid_sec/L_/Lupdating_sec/L_/Lerror_sec/L_/Llast_modified/L_/Ldate/L_/Lcrc32/Svalid_msec/Sheader_start/Sbody_start",$t);
    if(!$rs){
        var_dump($file);
    }
    unset($rs['_']);
    $meta = $rs;
    $t = fread($fh,1);
    $rs = unpack("Cetag_len",$t);
    $meta['etag_len'] = $rs['etag_len'];
    if($meta['etag_len']>0){
        $t = fread($fh,$meta['etag_len']);
        $meta['etag'] = $t;
    }
    $t = fread($fh,1);
    $rs = unpack("Cvary_len",$t);
    $meta['vary_len'] = $rs['vary_len'];
    if($meta['vary_len']>0){
        $t = fread($fh,$meta['vary_len']);
        $meta['vary'] = $t;
    }
    return $meta;
}
function make_dir($path,$perm=0777){
	if(!is_dir($path)){
		$str = dirname($path);
		if($str){
			make_dir($str.'/');
			@mkdir($path,$perm);
			chmod($path,$perm);
		}
	}
}
?>