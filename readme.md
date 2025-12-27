# 基于PHP构建的投票系统
此仓库由 [水之蔻放](http://kf.waterchisato.top)  组织编写  
![水之蔻放](https://logo.kf.waterchisato.top/zy/kf/Gemini_Generated_Image_j8ue0wj8ue0wj8ue.png "图片title")
[仓库链接](https://github.com/WaterChisato/Chisa_OnlineVoting/)  一個點擊網站就可以用的PHP投票系統，目前功能僅此而已  
全匿名，無法查看投票人，僅允許單IP進行投票  
管理面板默認簡潔明瞭，有高開發性，密碼默認為**114514**，可以刪除某個投票選項，但是無法修改投票人數（也是對投票做了一種公平機制）。  
> 在安裝的時候，把config.php的配置文件改一下，改完可以直接扔到服務器  
***我們建議你在部署后盡快登錄管理面板進行修改密碼，被盜概不負責***

| 界面             |                                 作用 |
| --------------- | ------------------------------------ |
| index.php       | 普通人投票面板                         |
| admin.php       | 管理投票面板，更改管理用户名称和密码       |
| admin_login.php | 管理员登录面板                         |
| config.php      | 服务器配置文件，一般就需要改配置即可       |      

  
cofig.php的关于数据库代码如下   
~~~
$host = 'localhost';
$dbname = '數據庫名'; 
$username = '帳號'; 
$password = '密碼';
// 按道理来说只需要改以上文字内容就完全OK
~~~
如果你要修改主投票背景图，就修改 index.php 的
~~~
            background: url('https://logo.kf.waterchisato.top/tybj.jpg') no-repeat center center fixed;
~~~
中的
~~~
https://logo.kf.waterchisato.top/tybj.jpg
//换成你自己的图片源就OK
~~~
[EN](https://github.com/WaterChisato/Chisa_OnlineVoting/blob/main/readme-en.md)  

