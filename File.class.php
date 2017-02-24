<?php
/**
 *开源项目：所有关于文件目录操作的功能
 *操作1：单文件上传功能 upload
 *操作2：多文件上传功能 mulUpload
 *操作3：文件下载 downLoad
 *操作4：查看单级目录结构 showDirectory
 *操作5：递归查看目录结构 showDirectories
 *操作6：查看文件或文件夹的大小 dirSize
 *操作7：按关键字递归查找文件 findFilesByKeyword
 *操作8：递归删除非空目录 deleteDir
 *操作9：递归删除空目录 deleteEmptyDir
 *操作10：递归创建目录 makeDir
 *操作11：剪切/复制目录到目录 moveDirToDestination
 *操作12：剪切文件到目录 moveFileToDir
 *操作13：显示文件或目录的详细信息 showFileInfo
 *操作14：创建文件并写入内容 createFile
 *操作15：向文件写入内容 insertContentToFile
 *操作16：文件重命名 renameFile
 *操作17：删除文件 removeFile
 */

class File{
    //上一次操作的错误提示
    public $error;

    /**
     *单文件上传
     *@param $file array 要上传的文件数组信息
     *@param $allow array 允许上传的文件类型
     *@param $size int 允许上传文件的大小
     *@param $path string 上传文件保存的路径
     *@return $fileName string 文件上传成功后的新文件名
     */
    public function upload($file,$allow,$size,$path='./upload'){
        $path = str_replace('\\','/',$path);
        //判断上传文件是否是一个合理的文件
        if(!is_array($file)){
            $this->error = '上传文件不是一个合理的文件';
            return false;
        }
        //判断上传文件是否是允许上传文件的类型
        if(!in_array($file['type'],$allow)){
            $this->error = '不允许上传的文件类型';
            return false;
        }
        //判断上传文件的大小是否符合允许上传文件的大小
        if($file['size']>$size){
            $this->error = '上传文件大小超过允许上传文件大小';
            return false;
        }
        //判断文件是否是通过HTTP POST上传的
        if(!is_uploaded_file($file['tmp_name'])){
            $this->error = '文件不是通过http上传的';
            return false;
        }
        //文件上传错误判断
        if($file['error']>0){
            switch($file['error']){
                case 1:
                    $this->error = '上传的文件超过了 php.ini 中 upload_max_filesize 选项限制的值';
                    return false;
                break;
                case 2:
                    $this->error = '上传文件的大小超过了 HTML 表单中 MAX_FILE_SIZE 选项指定的值';
                    return false;
                break;
                case 3:
                    $this->error = '文件只有部分被上传';
                    return false;
                break;
                case 4:
                    $this->error = '没有文件被上传';
                    return false;
                break;
                case 6:
                    $this->error = '找不到临时文件夹';
                    return false;
                break;
                case 7:
                    $this->error = '文件写入失败';
                    return false;
                break;
            }
        }
        //判断上传文件保存路径是否存在
        if(!file_exists($path)){
            mkdir($path);
        }
        //生成新的文件名
        $newName = $this->getNewFileName($file['name']);
        if(move_uploaded_file($file['tmp_name'],$path.'/'.$newName)){
            return $newName;
        }else{
            $this->error = '文件上传失败';
            return false;
        }
    }

    /**
     *多文件上传
     *@param $fileArr array 文件数组
     *@param $allow array 允许上传的文件类型
     *@param $size int 允许上传单个文件的大小
     *@param [$path='./upload'] string 上传文件的保存位置
     *@param $fileNameArr array 新的文件名数组
     */
    public function mulUpload($fileArr,$allow,$size,$path='./upload'){
        $filename = array();
        foreach($fileArr as $key=>$val){
            foreach($val as $k=>$v){
                if($key=='name'){
                    if($v!==''){
                         $filename[$k]['name'] = $v;
                    }
                }elseif($key=='type'){
                    if($v!==''){
                        $filename[$k]['type'] = $v;
                    }
                }elseif($key=='tmp_name'){
                    if($v!==''){
                        $filename[$k]['tmp_name'] = $v;
                    }
                }elseif($key=='error'){
                    if($v!==4){
                        $filename[$k]['error'] = $v;
                    }
                }else{
                    if($v!==0){
                        $filename[$k]['size'] = $v;
                    }
                }

            }
        }
        $fileNameArr = array();
        $path = str_replace('\\','/',$path);
        //如果文件保存目录不存在则要创建目录
        if(!file_exists($path)){
            mkdir($path);
        }
        foreach($filename as $val){
            if(!$fileNameArr[] = $this->upload($val,$allow,$size,$path)){
                $this->error = '文件上传出错了';
                foreach($fileNameArr as $val){
                    if($val!==false){
                        $this->removeFile($path.'/'.$val);
                    }
                }
                return false;
            }
        }
        return $fileNameArr;
    }

    /**
     *文件下载
     *@param $filePath string 要下载的文件的路径
     */
    public function downLoad($filePath){
        $filePath = str_replace('\\','/',$filePath);
        $basename = basename($filePath);
        header("Content-Type:application/octet-stream");
        header("Content-Disposition:attachment;filename=".$basename);
        $this->error = file_get_contents($filePath);
    }

    /**
     *生成新的文件名
     *@param $oldName string 旧的文件名
     *@return $newName string 新的文件名
     */
    public function getNewFileName($oldName){
        $newName = '';
        //获取文件的后缀名
        $ext = pathinfo($oldName)['extension'];
        //生成六位随机数
        $chars = array_merge(range('a','z'),range('A','Z'));
        //打乱数组的顺序
        shuffle($chars);
        $newName .= implode(array_slice($chars,0,6),'');
        $newName .= time();
        $newName .= '.'.$ext;
        return $newName;
    }

    /**
     *查看单级目录结构
     *@param $path string 要查看的目录路径
     *@param $arr array 目录结构
     */
    public function showDiretory($path){
        $path = str_replace('\\','/',$path);
        $arr = array();
        //判断路径是否存在
        if(!file_exists($path)){
            $this->error = '文件或目录不存在';
            return false;
        }
        if(is_dir($path)){
            $handle = opendir($path);
            while(false !== $file = readdir($handle)){
                $subPath = $path . '/' . $file;
                if(is_dir($subPath)){
                    $arr['dir'][] = $file;
                }else{
                    $arr['file'][] = $file;
                }
            }
            closedir($handle);
        }else{
            $arr['file'][] = basename($path);
        }
        return $arr;
    }

    /**
     *递归查看目录结构
     *@param $path string 目录路径
     *@return $arr array 目录结构
     */
    public function showDiretories($path){
        $path = str_replace('\\','/',$path);
        static $arr = array();
        //判断路径是否存在
        if(!file_exists($path)){
            $this->error = '文件或目录不存在';
            return false;
        }
        if(is_dir($path)){
            $handle = opendir($path);
            $flag = true;
            while(false !== $file = readdir($handle)){
                $subPath = $path . '/' . $file;
                $subPath = str_replace('//','/',$subPath);
                if($file=='.'||$file=='..'){
                    continue;
                }
                if(is_dir($subPath)){
                    $this->showDiretorys($subPath);
                }else{
                    $flag = false;
                    $arr[dirname($subPath)][] = $file;
                }
            }
            if($flag){
                $arr[$path] = null;
            }
            closedir($handle);
        }else{
            $arr[] = $path;
        }
        return $arr;
    }

    /**
     *查看文件或文件夹的大小
     *@param $path string 要查看大小的文件或目录路径
     *@return $size float 文件或目录的大小
     */
    public function dirSize($path){
        $path = str_replace('\\','/',$path);
        static $size = 0;
        //判断文件或目录是否存在
        if(!file_exists($path)){
            $this->error = '文件或目录不存在';
            return false;
        }
        if(is_dir($path)){
            $handle = opendir($path);
            while(false !== $file = readdir($handle)){
                $subPath = $path . '/' . $file;
                if($file=='.'||$file=='..'){
                    continue;
                }
                if(is_dir($subPath)){
                    $this->getSize($subPath);
                }else{
                    $size += filesize($subPath);
                }
            }
            closedir($handle);
        }else{
            return filesize($path);
        }
        return $this->getSize($size);
    }

    /**
     *返回带有单位的文件大小
     *@param $size int 原始的文件大小
     *@return $hsize float 带有单位的文件大小
     */
    private function getSize($size){
        $arr = array('Byte','KB','MB','GB','TB','EB');
        $i = 0;
        while($size>1024){
            $size /= 1024;
            $i++;
        }
        $hsize = round($size,2);
        $hsize .= $arr[$i];
        return $hsize;
    }

    /**
     *按关键字递归查找文件
     *@param $path string 要查找文件的目录
     *@param $keyword string 要查找的关键字
     *@return $files array 查找到的文件数组
     */
    public function findFilesByKeyword($path,$keyword){
        $path = str_replace('\\','/',$path);
        static $files = array();
        //判断文件或目录是否存在
        if(!file_exists($path)){
            $this->error = '文件或目录不存在';
            return false;
        }
        if(is_dir($path)){
            $handle = opendir($path);
            while(false !== $file = readdir($handle)){
                $subPath = $path . '/' . $file;
                $subPath = str_replace('//','/',$subPath);
                if($file=='.'||$file=='..'){
                    continue;
                }
                if(is_dir($subPath)){
                    $this->findFilesByKeyword($subPath,$keyword);
                }else{
                    if(strpos($file,$keyword)!==false){
                        $files[] = $subPath;
                    }
                }
            }
            closedir($handle);
        }
        return $files;
    }

    /**
     *递归删除非空目录
     *@param $path string 要删除的目录
     */
    public function deleteDir($path){
        $path = str_replace('\\','/',$path);
        //判断目录是否存在
        if(!file_exists($path)){
            $this->error = '文件或目录不存在';
            return false;
        }
        if(!is_dir($path)){
            $this->error = $path.'不是一个目录';
            return false;
        }
        if(is_dir($path)){
            $handle = opendir($path);
            while(false !== $file = readdir($handle)){
                if($file=='.'||$file=='..'){
                    continue;
                }
                $subPath = $path . '/' . $file;
                if(is_dir($subPath)){
                    $this->deleteDir($subPath);
                }else{
                    unlink($subPath);
                }
            }
            closedir($handle);
            if(rmdir($path)){
                return true;
            }else{
                $this->error = '删除失败';
                return false;
            }
        }else{
            unlink($path);
        }
    }

    /**
     *递归删除空目录
     *@param $path string 要删除的目录路径
     */
    public function deleteEmptyDir($path){
        $path = str_replace('\\','/',$path);
        //判断目录路径是否存在
        if(!file_exists($path)){
            $this->error = '文件或目录不存在';
            return false;
        }
        if(!is_dir($path)){
            $this->error = $path.'不是一个目录';
            return false;
        }
        if(is_dir($path)){
            $handle = opendir($path);
            $flag = true;
            while(false !== $file = readdir($handle)){
                if($file=='.'||$file=='..'){
                    continue;
                }
                $subPath = $path . '/' . $file;
                if(is_dir($subPath)){
                    $this->deleteEmptyDir($subPath);
                }
                $flag = false;
            }
            closedir($handle);
            if($flag){
                if(rmdir($path)){
                    return true;
                }else{
                    return false;
                }
            }
        }
    }

    /**
     *递归创建目录
     *@param $path string 在此目录下创建目录
     *@param $destination string 创建的目标目录
     */
    public function makeDir($path,$destination){
        $path = str_replace('\\','/',$path);
        $destination = str_replace('\\','/',$destination);
        //判读目录是否存在
        if(!file_exists($path)){
            $this->error = '目录不存在';
            return false;
        }
        if(is_dir($path)){
            $handle = opendir($path);
            $dirs = explode('/',$destination);
            $subPath = $path . '/' . $dirs[0];
            if(!file_exists($subPath)){
                if(!mkdir($subPath)){
                    $this->error = '目录创建失败';
                    return false;
                }
            }
            if(count($dirs)>1){
                $dir = implode('/',array_slice($dirs,1));
                $this->makeDir($subPath,$dir);
            }
            closedir($handle);
            return true;
        }else{
            $this->error = $path.'不是一个目录';
            return false;
        }
    }

    /**
     *剪切/复制目录到目录
     *@param $source string 源目录路径
     *@param $destination string 目标目录路径
     *@param [$option='x'] string 参数'x'为剪切,'v'为复制
     */
    public function moveDirToDestination($source,$destination,$option='x'){
        $source = str_replace('\\','/',$source);
        $destination = str_replace('\\','/',$destination);
        //判断目录或者文件是否存在
        if(!file_exists($source)||!file_exists($destination)){
            $this->error = '文件或目录不存在';
            return false;
        }
        if(!is_dir($source)){
            $this->error = $source.'不是一个目录';
            return false;
        }
        if(!is_dir($destination)){
            $this->error = $destination.'不是一个目录';
            return false;
        }
        if(strpos($destination,$source)===0){
            $this->error = '目标目录是源目录的子目录';
            return false;
        }
        //源目录下的目录结构
        $sourceDirs = $this->showDiretorys($source);
        $sourceDir = array_keys($sourceDirs);
        //源目录的上一级目录
        $sourcePrev = substr($source,0,strrpos($source,'/')+1);
        $subSourceDirs = array();
        foreach($sourceDir as $key=>$val){
            $subSourceDirs[$key] = ltrim($val,$sourcePrev);
        }
        //在目标目录中创建源目录中相应的目录
        foreach($subSourceDirs as $val){
            $this->makeDir($destination,$val);
        }
        //复制文件
        foreach($sourceDirs as $key=>$val){
            if($val){
                foreach($val as $v){
                    //源文件路径
                    $fileSourcePath = $key.'/'.$v;
                    //目标文件路径
                    $fileDestinationPath = $destination.'/'.ltrim($fileSourcePath,$sourcePrev);
                    copy($fileSourcePath,$fileDestinationPath);
                }
            }
        }
        if($option=='x'){
            //删除原目录
            $this->deleteDir($source);
        }
        return true;
    }

    /**
     *剪切/复制文件到目录
     *@param $source string 源文件路径
     *@param $destination string 目标目录路径
     *@param [$option='x'] string 参数'x'为剪切,'v'为复制
     */
    public function moveFileToDir($source,$destination,$option='x'){
        $source = str_replace('\\','/',$source);
        $destination = str_replace('\\','/',$destination);
        //判断文件和目录是否存在
        if(!file_exists($source)||!file_exists($destination)){
            $this->error = '文件或目录不存在';
            return false;
        }
        if(!is_file($source)){
            $this->error = $source.'不是一个文件';
            return false;
        }
        if(!is_dir($destination)){
            $this->error = $destination.'不是一个目录';
            return false;
        }
        $basename = basename($source);
        if($option=='x'){
            if(!copy($source,$destination.'/'.$basename)&&!unlink($source)){
                $this->error = '文件剪切失败';
                return false;
            }
        }else{
            if(!copy($source,$destination.'/'.$basename)){
                $this->error = '文件复制失败';
                return false;
            }
        }
        return true;
    }

    /**
     *显示文件或目录的详细信息
     *@param $path string 文件或目录的路径
     *@return $info array 文件或目录的详细信息
     */
    public function showFileInfo($path){
        $info = array();
        $path = str_replace('\\','/',$path);
        //判断文件或目录是否存在
        if(!file_exists($path)){
            $this->error = '文件或目录不存在';
            return false;
        }
        //检测文件或目录的读写执行权限
        $info['readable'] = is_readable($path) ? true : false;
        $info['writeable'] = is_writeable($path) ? true : false;
        $info['executable'] = is_executable($path) ? true : false;
        //检测文件或目录的大小
        $info['size'] = $this->dirSize($path);
        //检测类型
        $info['type'] = filetype($path);
        //文件或目录的创建访问修改时间
        $info['ctime'] = filectime($path);
        $info['mtime'] = filemtime($path);
        $info['atime'] = fileatime($path);
        return $info;
    }

    /**
     *创建文件并写入内容
     *@param $filename string 文件名
     *@param [$path='./'] string 在此目录下创建文件
     *@param [$content=''] string 要写入的内容
     */
    public function createFile($filename,$path='./',$content=''){
        $path = str_replace('\\','/',$path);
        //判断目录是否存在
        if(!file_exists($path)){
            $this->error = '目录不存在';
            return false;
        }
        //判断文件名是否合法
        if($this->checkFileName($filename)){
            $filePath = $path.'/'.$filename;
            if(!file_exists($filePath)){
                if(touch($filePath)){
                    if(file_put_contents($filePath,$content)){
                        return true;
                    }
                }else{
                    $this->error = '文件创建失败';
                    return false;
                }
            }else{
                $this->error = '文件已存在';
                return false;
            }
        }
    }

    /**
     *向文件写入内容
     *@param $file string 文件路径
     *@param $content string 写入文件的内容
     *@param [$option=0] int 附件参数(0覆盖写入,1追加写入)
     */
    public function insertContentToFile($file,$content,$option=0){
        $file = str_replace('\\','/',$file);
        //判断文件是否存在
        if(!file_exists($file)){
            touch($file);
        }
        if($option===0){
            if(!file_put_contents($file,$content)){
                $this->error = '内容写入失败';
                return false;
            }
        }else{
            if(!file_put_contents($file,$content,FILE_APPEND)){
                $this->error = '内容追加失败';
                return false;
            }
        }
        return true;
    }

    /**
     *文件重命名
     *@param $oldname string 旧的文件名
     *@param $newname string 新的文件名
     */
    public function renameFile($oldname,$newname){
        //判断旧文件是否存在
        if(!file_exists($oldname)){
            $this->error = '文件不存在';
            return false;
        }
        //判断旧文件是否是一个文件
        if(!is_file($oldname)){
            $this->error = $oldname.'不是一个文件';
            return false;
        }
        //判断新文件名是否合法
        if($this->checkFileName($newname)){
            if(!rename($oldname,dirname($oldname).'/'.$newname)){
                $this->error = '文件重命名失败';
                return false;
            }
        }
        return true;
    }

    /**
     *检测文件名是否合法
     *@param $filename string 文件名
     */
    public function checkFileName($filename){
        //文件名不能带 / * <> \ | ? 等字符
        $pattern = "/[\/,\*,<>,\\\|\?]/";
        if(preg_match($pattern,$filename)){
            $this->error = '文件名不合法';
            return false;
        }else{
            return true;
        }
    }

    /**
     *删除文件
     *@param $filename 文件名
     */
    public function removeFile($filename){
        //判断文件是否存在
        if(!file_exists($filename)){
            $this->error = '文件不存在';
            return false;
        }
        //判断是否是一个文件
        if(!is_file($filename)){
            $this->error = $filename.'不是一个文件';
            return false;
        }
        if(!unlink($filename)){
            $this->error = '文件删除失败';
            return false;
        }
        return true;
    }
}
//header('content-type:text/html;charset=utf-8');
//$obj = new File();
//var_dump($obj->removeFile('images_on.bmp'));
