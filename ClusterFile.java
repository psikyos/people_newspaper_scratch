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
			//��Ŀ���ļ����
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
				//ȷ������˫���ŵ�λ��
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
				//ȷ������˫���ŵ�λ��
				int last_pos=content.indexOf("\");",first_pos+keyword.length());
				param_file=content.substring(first_pos+keyword.length(),last_pos);
			}
		}
		cfg_input.close();
		return param_file;
	}
	
	public static void cluster_in_one(String base_dir,String combined_file) throws Exception//��Ŀ¼�µ������ļ�д�뵽һ���ļ�
	{
		File file=new File(base_dir);
		if(!file.exists())//�����ȡ��Ŀ¼������
		{
			System.out.println(base_dir+" does not exists.");
			return;
		}
		if(file.isDirectory())//�����Ŀ¼,�Ŵ����Ŀ¼�µ��ļ�����Ŀ¼
		{
			System.out.println(base_dir+" is directory.");
			File[] file_list=file.listFiles();
			for(int i=0;i<file_list.length;i++)
			{
				if(file_list[i].isFile())
				{
					System.out.println(file_list[i].getPath());
					//�ϲ��ļ�����
					process_file_content(file_list[i],combined_file);
				}
				if(file_list[i].isDirectory())
					cluster_in_one(file_list[i].getPath(),combined_file);
			}
		}		
	}
	
	/** ����һ���ļ�,��д��һ�����ݴ��� 
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
		//Ӧ�ô���һ��HTML
		byte cl_content[]=clear_html(content);
		target_file.write(cl_content);
		//target_file.write(content);//���������html��ǩ��ע�͵��������У��������ע��
		input.close();
		target_file.close();
	}
	
	/** �滻����Ŀ�꣺ 
	 * 1.��<P��ʼ����>��β��,ɾ��;
	 * 2.��</P>��ʼ�ģ��滻Ϊ�س�,windows�µ�0D0A;
	 * 3.&nbsp;�滻Ϊһ���ո�.
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
		for(;true;)//�ҵ���ֵ�<P��ʼ�ı�ǩ
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
		ori_content=ori_content.replace(" ","");//ȥ���ո�
		ori_content=ori_content.replace("\t","");
		ori_content=ori_content.replace("��","");
		return ori_content.getBytes();
	}
	
	public static String long_html_tag(String p_start_kw,String p_end_kw,String content)
	{
		//String p_start_kw="<P",p_end_kw=">";		
		int first_pos=-1,last_pos=-1,section_pos=0;
		for(;true;)//�ҵ���ֵ�<P��ʼ�ı�ǩ
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
	
	/** ���Ŀ���ļ�����,�����ڳ���տ�ʼʱʹ��
	 */
	public static void clear_file_content(String combined_file) throws Exception
	{
		RandomAccessFile the_file=new RandomAccessFile(combined_file,"rw");
		the_file.setLength(0);
		the_file.close();
	}
}