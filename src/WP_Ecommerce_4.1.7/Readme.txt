==============
INSTALLATION
==============
just copy dibspw.php file in your Wordpress E-commerce wp-e-commerce/wpsc-merchants/ folder.
Login in wordress, go to Settings->Store->Payments
Find "DIBS Payment Window" and click Settings under it. 
Fill settings like merchantid, hmac code, partnerid(if you have it) e.t.c 
Enable module. 
Set test mode and try to test it. 
Suggested statuses for orders - Pending payment status: Order Received
				Success payment status: Accepted payment - must for email sending for versions >= 3.8.9 !!!
				Cancel payment status:  Close Order/Incomplete Sale.
  
ver. 4.1.6 Release date: 05.22.2014
- Module was completly rewrited. Reduced a huge amount of code tha was not necessary.
- Fixed bugs and notices. 
- Code styling in WP way. 
- On invoice payments acquirer parameters saves in ordeer details. 

ver. 4.1.7 Release date: 08.10.2014
- added possibility to add DIBS logos in admin
  http://tech.dibspayment.com/logos#check-out-logos
