<?php
/**
 * 抓取人民日报内容,从远去的一个日期一直抓到今天
 * @copyright PSIKYO Corp. 2016
 */
include("config.php");
if(!file_exists(BASE_DIR))//基础目录的保证
    mkdir(BASE_DIR,0700);    

//抓取其他天的
//$start_url="http://paper.people.com.cn/rmrb/html/2016-10/12/nbs.D110000renmrb_01.htm";//页面为utf-8
long_long_ago($start_url);//start_url在config.php中

//file_clear($dayurl);//先清空
//假定抓取某一天的
//分析某天的url,有2个等级的列表
//one_day($dayurl);

//某页的基础url
//$url="http://paper.people.com.cn/rmrb/html/2016-10/12/nw.D110000renmrb_20161012_1-01.htm";
//one_article($url);
?>
<?php
function long_long_ago($baseurl)//从很多天以前抓取直到今天的文章
{
    $baseline_url_arr=make_url($baseurl);
    $url_size=count($baseline_url_arr);
    if($url_size>0)
    {
        for($i=0;$i<$url_size;$i++)
        {
            $dayurl=$baseline_url_arr[$i];
            file_clear($dayurl);//先清空
            //假定抓取某一天的
            //分析某天的url,有2个等级的列表
            one_day($dayurl);
        }
        echo "All done.";
    }
}

/** 处理一天的列表,共2个等级的列表
 * 第1级别，大纲级别，比如：第01版：要闻 第05版：评论
 * 第2级别，具体的文章链接级别，比如“第01版：要闻”下的某文章列表为：《李克强出席中国—葡语国家经贸合作论坛》、《推动1亿非户籍人口在城市落户》
 * 链接《推动1亿非户籍人口在城市落户》的url，通过one_day_pages()、one_page_titles()，最终将传给one_article()函数
 */ 
function one_day($dayurl)
{
    $day_pages_arr=one_day_pages($dayurl);//某天,取得所有版面
    $base_url=base_url_endwith_slash($dayurl);
    if($day_pages_arr!=null)
    {
        $pages_size=count($day_pages_arr);//所有的版面数量
        for($i=0;$i<$pages_size;$i++)
        {
            $page_url=sprintf("%s%s",$base_url,$day_pages_arr[$i]);
            $page_titles_arr=one_page_titles($page_url);//取得某个大版面下的所有标题的链接
            if($page_titles_arr!=null)
            {
                $titles_size=count($page_titles_arr);
                for($j=0;$j<$titles_size;$j++)
                {
                    $article_url=sprintf("%s%s",$base_url,$page_titles_arr[$j]);
                    $one_article_content=one_article($article_url);//取得某篇文章的内容
                    //将该文章内容存入某个文本
                    file_save($dayurl,$one_article_content);                    
                }
            }
            echo $page_url."\n";
        }
    }
    echo "One day done.\n";
}

//得到每日的大纲版面
function one_day_pages($dayurl)
{
    $start_keyword="<!-- list starting -->";//2个等级的列表都是这个关键字
    $end_keyword="<!-- list endding -->";
    $list_keyword="<!--版面目录-->";//在版面目录中寻找list
    $list_link_kw="<a id=pageLink href=";  //大版面连接的关键字
    $list_link_end_kw=">";    
    
    $ch=wx_curl_init($errmsg);
    curl_setopt($ch,CURLOPT_URL,$dayurl);;
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
    $data=curl_exec($ch) or die(curl_error($ch));//取得当天报纸的页面内容
    
    //分析页面内容
    //1.定位版面
    $list_pos=mb_strpos($data,$list_keyword,0);
    if($list_pos===false)
    {
        echo "List not found.Maybe it changes of the html code or check the encode of page.";
        return null;
    }
    //2.在版面内找版面目录列表
    $first_pos=strpos($data,$start_keyword,$list_pos);
    if($first_pos===false)
        return null;
    $last_pos=strpos($data,$end_keyword,$first_pos);
    if($last_pos===false)
        return null;
    
    //版面目录的内容
    $list_content=substr($data,$first_pos+strlen($start_keyword),$last_pos-$first_pos-strlen($start_keyword));
    $first_pos=false;
    $last_pos=false;
    $section_pos=0;
    $i=0;
    $whole_page_link=array();    
    do
    {
        $first_pos=strpos($list_content,$list_link_kw,$section_pos);
        if($first_pos===false)            break;
        $last_pos=strpos($list_content,$list_link_end_kw,$first_pos);
        if($last_pos===false)            break;
        //一个版面的链接
        $one_link=substr($list_content,$first_pos+strlen($list_link_kw),$last_pos-$first_pos-strlen($list_link_kw));
        $whole_page_link[$i]=$one_link;
        $i++;
        $section_pos=$last_pos;
    }while(1);//($first_pos!=false&&$last_pos!=false);
    curl_close($ch);    
    return $whole_page_link;
    
}

/** 从一个版面里，获得该版面的所有标题文章的链接,比如版面：01.要闻
 */
function one_page_titles($page_url)
{
    $start_keyword="<!-- list starting -->";//2个等级的列表都是这个关键字
    $end_keyword="<!-- list endding -->";
    $title_link_kw="<a href=";  //大版面连接的关键字
    $title_link_end_kw=">";
    $title_keyword="<!--新闻标题-->";//在新闻标题中寻找list
    
    $ch=wx_curl_init($errmsg);
    curl_setopt($ch,CURLOPT_URL,$page_url);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
    $data=curl_exec($ch) or die(curl_error($ch));
    
    //分析页面内容
    $title_list_pos=strpos($data,$title_keyword);
    if($title_list_pos===false)
    {
        echo "Title list not found.\n";
        return null;
    }
    $first_pos=strpos($data,$start_keyword,$title_list_pos);
    if($first_pos===false)
        return null;
    $last_pos=strpos($data,$end_keyword,$first_pos);
    $title_content=substr($data,$first_pos+strlen($start_keyword),$last_pos-$first_pos-strlen($start_keyword));
    
    /** 分析该大版面下的标题数组,比如 《推动1亿非户籍人口在城市落户》*/
    $first_pos=false;
    $last_pos=false;
    $section_pos=0;
    $i=0;
    $whole_title_link=array();
    do
    {
        $first_pos=strpos($title_content,$title_link_kw,$section_pos);
        if($first_pos===false)            break;
        $last_pos=strpos($title_content,$title_link_end_kw,$first_pos);
        if($last_pos===false)        break;
        $one_title=substr($title_content,$first_pos+strlen($title_link_kw),$last_pos-$first_pos-strlen($title_link_kw));
        $whole_title_link[$i]=$one_title;        
        $i++;
        $section_pos=$last_pos;
    }while(1);    
    curl_close($ch);
    return $whole_title_link;
}

/** 从传入的每日url里拆分出基本url,结尾以/结束
 */ 
function base_url_endwith_slash($dayurl)
{
    $final_pos=strrpos($dayurl,"/");
    if($final_pos===false)
        return $dayurl;//返回未处理的字符串
    return substr($dayurl,0,$final_pos+1);
}

/** 处理一个文章的页面,标题没有抓取
 * 标题长这样：
<h3>国办印发《方案》</h3>
<h1>推动1亿非户籍人口在城市落户</h1>
<h2></h2>

<h4></h4>
 * 可以直接搜索
 */ 
function one_article($url)
{
    $start_keyword="<!--enpcontent-->";
    $end_keyword="<!--/enpcontent-->";
    
    $ch=wx_curl_init($errmsg);
    curl_setopt($ch,CURLOPT_URL,$url);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
    $data=curl_exec($ch) or die(curl_error($ch));
    
    //分析页面内容    
    $first_pos=strpos($data,$start_keyword);
    if($first_pos===false)
    {
        echo "first pos not found.";
        return "";
    }
        
    $last_pos=strpos($data,$end_keyword,$first_pos);
    if($last_pos===false)
    {
        echo "last pos not found.";
        return "";
    }
    $material=substr($data,$first_pos+strlen($start_keyword),$last_pos-$first_pos-strlen($start_keyword));
    //echo htmlspecialchars($material);
    curl_close($ch);
    return $material;    
}

function file_save($url,$content)
{
    $year;$month="";$day="";
    get_date_from_url($url,$year,$month,$day);
    $file_with_path=sprintf("%s/%s/%s/%s.txt",BASE_DIR,$year,$month,$day);
    $fp=fopen($file_with_path,"ab");
    if($fp!=null)
    {
        fwrite($fp,$content,strlen($content));
        fclose($fp);
    }
    else
        echo $file_with_path." can not be writed.\n";
}

function file_clear($url)
{
    $year;$month="";$day="";
    get_date_from_url($url,$year,$month,$day);
    $the_path=sprintf("%s/%s",BASE_DIR,$year);//年
    if(!file_exists($the_path))        mkdir($the_path,0700);
    
    $the_path=sprintf("%s/%s/%s",BASE_DIR,$year,$month);//月
    if(!file_exists($the_path))        mkdir($the_path,0700);    
    $file_with_path=sprintf("%s/%s.txt",$the_path,$day);
    $fp=fopen($file_with_path,"w");
    if($fp!=null)
        fclose($fp);
}

/** url示例:http://paper.people.com.cn/rmrb/html/2016-10/12/nbs.D110000renmrb_01.htm
 * 从倒数第二个斜杠提取日期;从倒数第三个斜杠提取年和月
 * 参数传回从入参引用
*/
function get_date_from_url($url,&$year,&$month,&$day)
{
    $sepator="/";
    $day_last_pos=strrpos($url,$sepator);
    if($day_last_pos===false)
        return ;
    $day_url=substr($url,0,$day_last_pos);//应该变为http://paper.people.com.cn/rmrb/html/2016-10/12

    $day_start_pos=strrpos($day_url,$sepator);//day的start位置,也是年月的结束位置
    if($day_start_pos===false)
        return ;
    $the_day=substr($day_url,$day_start_pos+strlen($sepator),2);//天数为2位
    
    $date_url=substr($day_url,0,$day_start_pos);//应该变为http://paper.people.com.cn/rmrb/html/2016-10    
    $date_start_pos=strrpos($date_url,$sepator);
    if($date_start_pos===false)
        return ;
    $the_date=substr($date_url,$date_start_pos+strlen($sepator),7);
    
    $dt_arr=explode("-",$the_date);//形式为2016-10
    //$year_month=$the_date;
    $year=$dt_arr[0];
    $month=$dt_arr[1];
    $day=$the_day;
    //echo "The year:".$year.".The month:".$month.". The day:".$the_day."<br>";
}

/** 根据计算的日期,构造url
*/
function make_url($url)
{
    $year="";$month="";$day="";
    $date_unix_arr=calc_date($url);//以unix时间戳的日期数组
    get_date_from_url($url,$year,$month,$day);
    $ymd=sprintf("%s-%s/%s",$year,$month,$day);
    $date_cnt=count($date_unix_arr);
    $baseline_url_arr=array();
    for($i=0;$i<$date_cnt;$i++)
    {
        $replaced=date("Y-m/d",$date_unix_arr[$i]);
        $artifical_url=str_replace($ymd,$replaced,$url);
        $baseline_url_arr[$i]=$artifical_url;        
    }
    return $baseline_url_arr;
}

/** 计算从远古的一天到今天的所有日期,为了构造URL
 */ 
function calc_date($url)
{
    get_date_from_url($url,$year,$month,$day);
    $thetime=mktime(0,0,0,$month,$day,$year);
    //$today=time();
    $today=mktime(0,0,0,date("m")  ,date("d"),date("Y"));//当天0点    
    $the_date_arr=array();
    for($i=0;;$i++)
    {
        $next_time=mktime(0,0,0,date("m",$thetime), date("d",$thetime)+$i,date("Y",$thetime));
        if($next_time>$today)
            break;
        else
            $the_date_arr[$i]=$next_time;
    }
    return $the_date_arr;    
    //for($i=0;$i<count($the_date_arr);$i++)        echo date("Y-m-d",$the_date_arr[$i])." ";
}

function curl_writelog($msg)
{
    //date_default_timezone_set('Asia/Shanghai'); //不加此语句,则按照格林威治时间显示
	$fp=fopen(BASE_DIR."/debug.txt","a+");
	$finalmsg=sprintf("%s %s\r\n",date("Y-m-d H:i:s",time()),$msg);
	fwrite($fp,$finalmsg,strlen($finalmsg));
	fclose($fp);
}

/** 初始化curl
 * 返回值是由curl_init初始化的ch,类似于一个浏览器,如果失败ch为false
*/
function wx_curl_init(&$errmsg)
{
    if(!function_exists("curl_init"))
    {
        $errmsg="curl function does not exists.";
        return false;
    }
    $ch=curl_init();
    if(curl_error($ch))
    {
        $errmsg=curl_error($ch);
        return false;
    }
    else
        return $ch;
}
?>