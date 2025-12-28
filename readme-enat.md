# PHP-Based Voting System 
Developed by: AquaChibloom (http://kf.waterchisato.top)
Repository: https://github.com/WaterChisato/Chisa_OnlineVoting/

---

[ INTRODUCTION ]
This is a "plug-and-play" PHP voting system that can be used immediately upon website deployment. Currently, it focuses on providing essential voting functionality.

[ KEY FEATURES ]
* Complete Anonymity: Voter identities are fully anonymous and cannot be tracked.
* IP-Based Restriction: Only one vote is permitted per unique IP address to ensure fairness.
* User-Friendly Admin Panel: Simple, clear interface with high extensibility for developers.
* Security Mechanism: Default password is '114514'.
* Fairness Policy: Admins can delete voting options but cannot modify the number of votes.

[ INSTALLATION & SECURITY ]
* Installation: Modify the 'config.php' file and upload all files to your server.
* RECOMMENDATION: Log in to the admin panel and change your password immediately after deployment. The developer is not responsible for any security breaches caused by stolen default credentials.

[ FILE STRUCTURE ]
* index.php: Public voting interface for general users.
* admin.php: Admin panel for managing votes and changing credentials.
* admin_login.php: Administrator login portal.
* config.php: Server configuration file (database settings).

[ DATABASE CONFIGURATION (config.php) ]
Modify the following variables in config.php:
- $host = 'localhost';
- $dbname = 'Database_Name'; 
- $username = 'Account_Name'; 
- $password = 'Account_Password';
Note: Generally, only the values inside the quotes need to be changed.

[ CUSTOMIZATION ]
To change the main voting page background, modify the following line in 'index.php':
- Look for: background: url('https://logo.kf.waterchisato.top/tybj.jpg')
- Action: Replace the URL with your own image link.

[ ADMIN MANAGEMENT ]
Instructional image for changing username and password:
- Source: https://logo.kf.waterchisato.top/zy/md/ticket/Screenshot_2025-12-28-16-14-14-00_40deb401b9ffe8e1df2f1cc5ba480b12~2.jpg

---
End of Document
