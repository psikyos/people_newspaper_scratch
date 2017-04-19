import java.io.*;
import java.util.*;

public class ClusterFile
{
	public static void main(String args[]) throws Exception
	{
		File file=new File("./config.php");
		if(!file.exists())
		{
			System.out.println("The config file does not exists.");
			return;
		}
		String base_dir=get_base_dir(file);
		if(base_dir.equals("")!=true)
		{			
			//String target_fn="./combined.txt";
			String target_fn=get_final_file(file);
			//把目标文件清空
			clear_file_content(target_fn);
			cluster_in_one(base_dir,target_fn);
		}
	}
	
	public static String get_base_dir(File file) throws Exception
	{
		Scanner cfg_input=new Scanner(file);
		String keyword="define(\"BASE_DIR\",\"";
		String base_dir="";
		while(cfg_input.hasNext())
		{
			String content=cfg_input.nextLine();
			int first_pos=content.indexOf(keyword);
			if(first_pos!=-1)
			{
				//确定结束双引号的位置
				int last_pos=content.indexOf("\");",first_pos+keyword.length());
				base_dir=content.substring(first_pos+keyword.length(),last_pos);
			}
		}
		cfg_input.close();
		return base_dir;
	}
	
	public static String get_final_file(File file) throws Exception
	{
		Scanner cfg_input=new Scanner(file);
		String keyword="define(\"FINAL_FILE\",\"";
		String param_file="test.txt";
		while(cfg_input.hasNext())
		{
			String content=cfg_input.nextLine();
			int first_pos=content.indexOf(keyword);
			if(first_pos!=-1)
			{
				//确定结束双引号的位置
				int last_pos=content.indexOf("\");",first_pos+keyword.length());
				param_file=content.substring(first_pos+keyword.length(),last_pos);
			}
		}
		cfg_input.close();
		return param_file;
	}
	
	public static void cluster_in_one(String base_dir,String combined_file) throws Exception//将目录下的所有文件写入到一个文件
	{
		File file=new File(base_dir);
		if(!file.exists())//如果读取的目录不存在
		{
			System.out.println(base_dir+" does not exists.");
			return;
		}
		if(file.isDirectory())//如果是目录,才处理该目录下的文件和子目录
		{
			System.out.println(base_dir+" is directory.");
			File[] file_list=file.listFiles();
			for(int i=0;i<file_list.length;i++)
			{
				if(file_list[i].isFile())
				{
					System.out.println(file_list[i].getPath());
					//合并文件内容
					process_file_content(file_list[i],combined_file);
				}
				if(file_list[i].isDirectory())
					cluster_in_one(file_list[i].getPath(),combined_file);
			}
		}		
	}
	
	/** 读入一份文件,则写入一份数据存盘 
	 */
	public static void process_file_content(File fwp,String combined_file) throws Exception
	{
		long source_size=fwp.length();
		byte content[]=new byte[(int) source_size];
		RandomAccessFile input=new RandomAccessFile(fwp, "r");
		input.read(content);
		
		RandomAccessFile target_file=new RandomAccessFile(combined_file,"rw");
		long target_size=target_file.length();
		target_file.seek(target_size);
		//应该处理一下HTML
		byte cl_content[]=clear_html(content);
		target_file.write(cl_content);
		//target_file.write(content);//如果不处理html标签，注释掉以上两行，解除本行注释
		input.close();
		target_file.close();
	}
	
	/** 替换以下目标： 
	 * 1.以<P开始，以>结尾的,删除;
	 * 2.以</P>开始的，替换为回车,windows下的0D0A;
	 * 3.&nbsp;替换为一个空格.
	 * @throws UnsupportedEncodingException 
	 */
	public static byte[] clear_html(byte[] byte_content) throws UnsupportedEncodingException
	{
		String ori_content=new String(byte_content,"UTF-8");
		ori_content=long_html_tag("<P",">",ori_content);
		ori_content=long_html_tag("<FONT",">",ori_content);
		ori_content=long_html_tag("<SPAN",">",ori_content);
		ori_content=long_html_tag("<SCRIPT","</SCRIPT>",ori_content);
		ori_content=long_html_tag("<A",">",ori_content);
		/*String p_start_kw="<P",p_end_kw=">";		
		int first_pos=-1,last_pos=-1,section_pos=0;
		for(;true;)//找到奇怪的<P开始的标签
		{
			first_pos=ori_content.indexOf(p_start_kw,section_pos);
			if(first_pos==-1)
				break;
			last_pos=ori_content.indexOf(p_end_kw,first_pos);
			if(last_pos==-1)
				break;
			String special_p=ori_content.substring(first_pos,last_pos+1);			
			ori_content=ori_content.replace(special_p,"");
			section_pos=last_pos;
		}*/
		
		ori_content=ori_content.replace("<P></P>","\r\n");
		ori_content=ori_content.replace("</P>","\r\n");
		ori_content=ori_content.replace("<p>","");
		ori_content=ori_content.replace("</p>","\r\n");
		ori_content=ori_content.replace("<BR>","\r\n");
		ori_content=ori_content.replace("<SUB>","");
		ori_content=ori_content.replace("</SUB>","");
		ori_content=ori_content.replace("<SUP>","");
		ori_content=ori_content.replace("</SUP>","");		
		ori_content=ori_content.replace("<DIV>","");
		ori_content=ori_content.replace("</DIV>","");
		ori_content=ori_content.replace("</FONT>","");
		ori_content=ori_content.replace("</SPAN>","");
		ori_content=ori_content.replace("</A>","");
		
		ori_content=ori_content.replace("&nbsp;"," ");
		ori_content=ori_content.replace(" ","");//去除空格
		ori_content=ori_content.replace("\t","");
		ori_content=ori_content.replace("　","");
		return ori_content.getBytes();
	}
	
	public static String long_html_tag(String p_start_kw,String p_end_kw,String content)
	{
		//String p_start_kw="<P",p_end_kw=">";		
		int first_pos=-1,last_pos=-1,section_pos=0;
		for(;true;)//找到奇怪的<P开始的标签
		{
			first_pos=content.indexOf(p_start_kw,section_pos);
			if(first_pos==-1)
				break;
			last_pos=content.indexOf(p_end_kw,first_pos);
			if(last_pos==-1)
				break;
			String special_p=content.substring(first_pos,last_pos+1);			
			content=content.replace(special_p,"");
			section_pos=last_pos;
		}
		return content;
	}
	
	/** 清除目标文件内容,仅限于程序刚开始时使用
	 */
	public static void clear_file_content(String combined_file) throws Exception
	{
		RandomAccessFile the_file=new RandomAccessFile(combined_file,"rw");
		the_file.setLength(0);
		the_file.close();
	}
}