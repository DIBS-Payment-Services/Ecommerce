Installation
============
just copy <code>dibspw.php</code> file in your Wordpress E-commerce <code>wp-e-commerce/wpsc-merchants/</code> folder.
Setup
=====
Login in wordress, go to <b>Settings->Store->Payments</b>
Find "DIBS Payment Window" and click Settings under it. 
Fill settings like merchantid, hmac code, partnerid(if you have it) e.t.c 
Enable module. 
Set test mode and try to test it. 
Suggested statuses for orders.
- Pending payment status: Order Received
- Success payment status: Accepted payment - must for email sending for versions >= 3.8.9 !!!
- Cancel payment status:  Close Order/Incomplete Sale.

Changelog
=========  
ver. 4.1.6 Release date: 22.05.2014
- Module was completly rewrited. Reduced a huge amount of code tha was not necessary.
- Fixed bugs and notices. 
- Code styling in WP way. 
- On invoice payments acquirer parameters saves in ordeer details. 

ver. 4.1.7 Release date: 10.08.2014
- added possibility to add Nets logos in admin
  http://tech.dibspayment.com/logos#check-out-logos
ver. 4.1.8 Release date: 21.01.2015
- added support for DX platform
- small changes
