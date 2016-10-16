人民日报抓取程序使用方法：
1.修改config.php，给出一个人民日报的开始url，然后抓取从开始url的那个日期一直到今日的所有人民日报内容。
在命令行里运行
php people_newspaper.php
来获得抓取结果。
未登录状态下，只有当天的所有版面能够抓完。
抓取结果存储在data目录下，按“年/月”建立目录结构。一天为一个文件。

2.使用ClusterFile.java程序将data目录下的所有文件合并到一个文件combined.txt里，文件名在java源代码里修改。如果希望保留html标签，去掉clear_html()函数。

PSIKYO
16th,Oct,2016