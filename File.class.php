<?php
/**
 *开源项目：所有关于文件目录操作的功能
 *方法1：单文件上传功能 upload
 *方法2：多文件上传功能 multiUpload
 *方法3：文件下载 downLoad
 *方法4：生成新的文件名 getNewFileName
 *方法5：查看单级目录结构 showDirectory
 *方法6：递归查看目录结构 showDirectories
 *方法7：查看文件或文件夹的大小 dirSize
 *方法8：返回带有单位的文件大小 getSize
 *方法9：按关键字递归查找文件 findFilesByKeyword
 *方法10：递归删除非空目录 deleteDir
 *方法11：递归删除空目录 deleteEmptyDir
 *方法12：递归创建目录 makeDir
 *方法13：剪切/复制目录到目录 moveDirToDestination
 *方法14：剪切文件到目录 moveFileToDir
 *方法15：显示文件或目录的详细信息 showFileInfo
 *方法16：创建文件并写入内容 createFile
 *方法17：向文件写入内容 insertContentToFile
 *方法18：文件重命名 renameFile
 *方法19：检测文件名是否合法 checkFileName
 *方法20：删除文件 removeFile
 *方法21：获取错误信息 getError
 *方法22：压缩文件夹 zipFolder
 *方法23：加压文件夹 extractFolder
 */

class File{
    //上一次操作的错误提示
    private $error;

    /**
     *单文件上传
     *@param $file array 要上传的文件数组信息
     *@param $allow array 允许上传的文件类型
     *@param [$size=0] int 允许上传文件的大小，默认为0，不限制文件大小
     *@param $path string 上传文件保存的路径
     *@return $fileName string 文件上传成功后的新文件名
     */
    public function upload($file,$allow,$size=0,$path='./upload'){
        $path = str_replace('\\','/',$path);
        //判断上传文件是否是一个合理的文件
        if(!is_array($file)){
            $this->error = '上传文件不是一个合理的文件';
            return false;
        }
        //检测文件的真实MIME类型，防止文件已假冒的MIME类型上传
        $file['type'] = mime_content_type($file['tmp_name']);
        //判断上传文件是否是允许上传文件的类型
        if(!in_array($file['type'],$allow)){
            $this->error = '不允许上传的文件类型';
            return false;
        }
        //判断上传文件的大小是否符合允许上传文件的大小
        if($size!=0){
            if($file['size']>$size){
                $this->error = '上传文件大小超过允许上传文件大小';
                return false;
            }
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
     *@param [$size=0] int 允许上传文件的大小，默认为0，不限制文件大小
     *@param [$type=1] int 多文件上传类型，1表示文件域的name为数组file[]形式(默认)，2表示普通类型
     *@param [$path='./upload'] string 上传文件的保存位置
     *@return $fileNameArr array 新的文件名数组
     */
    public function multiUpload($fileArr,$allow,$size=0,$type=1,$path='./upload'){
        $filename = array();
        if($type==1){
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
        }elseif($type==2){
            foreach($fileArr as $val){
                if($val['name']!=''||$val['type']!=''||$val['tmp_name']!=''||$val['error']==0||$val['size']!=0){
                    $filename[] = $val;
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
                //遇到上传文件出错，删除部分上传的文件
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
        //判断要下载的文件是否存在
        if(!file_exists($filePath)){
            $this->error = '文件不存在';
        }
        //判断要下载的是否是一个文件
        if(!is_file($filePath)){
            $this->error = $filePath.'不是一个文件';
        }
        $basename = basename($filePath);
        header("Content-Type:application/octet-stream");
        header("Content-Disposition:attachment;filename=".$basename);
        file_get_contents($filePath);
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
        $chars = array_merge(range('a','z'),range('A','Z'),range(0,9));
        //打乱数组的顺序
        shuffle($chars);
        $newName .= implode(array_slice($chars,0,6),'');
        $newName .= time();
        $newName = md5($newName);
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
                    $this->showDiretories($subPath);
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
                    $this->dirSize($subPath);
                }else{
                    $size += filesize($subPath);
                }
            }
            closedir($handle);
        }else{
            return $this->getSize(filesize($path));
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

    /**
     *获取错误信息
     *@return $error string 详细的错误信息
     */
    public function getError(){
        return $this->error;
    }

    /**
     *压缩文件夹
     *@param $path string 文件路径
     */
    public function zipFolder($path){
        $zip = new ZipArchive();
        $filename = pathinfo($path)['filename'];
        $res = $zip->open($filename.'.zip',ZipArchive::CREATE);
        $dirs = $this->showDiretories($path);
        if($res){
            //key是目录名，val是文件名
            foreach($dirs as $key=>$val){
                if($val){
                    foreach($val as $v){
                        if(is_file($key.'/'.$v)){
                            $zip->addFile($key.'/'.$v);
                        }
                    }
                }
            }
        }
        $zip->close();
        return true;
    }

    /**
     *解压文件夹
     *@param $path string 要解压的文件路径
     *@param $to string 解压到指定的目录
     */
    public function extractFolder($path,$to){
        //判断指定目录是否存在
        if(!file_exists($to)){
            $this->error = $to.'不存在';
        }
        //判断解压包是否存在
        if(!file_exists($path)){
            $this->error = $path.'不存在';
        }
        $filename = pathinfo($path)['filename'];
        $zip = new ZipArchive();
        $res = $zip->open($path);
        if($res){
            if(!$zip->extractTo($to.'/'.$filename)){
                $this->error = '解压失败';
            }
        }
        $zip->close();
        return true;
    }
}
