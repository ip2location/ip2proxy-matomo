# IP2Proxy Matomo

## Description

This Matomo plugin generates proxy report based on the visitor's IP address. The details including proxy type, country, region, city, ISP, domain, usage type, ASN, and security threat.

You need a IP2Proxy BIN database to make this plugin works. Database is available for free at

[https://lite.ip2location.com](https://lite.ip2location.com/ip2proxy-lite) or [https://www.ip2location.com](https://www.ip2location.com/database/ip2proxy) for a commercial database.



## Installation / Update

1. Login into your Matomo administrator page.
2. Go to System > Plugins.
3. Click on the **Install New Plugins** button at the bottom of the page.
4. Search for **IP2Proxy** from the Marketplace.
5. Install and activate the plugin.
6. Upload IP2Proxy BIN database to **/misc** folder. 
7. Navigate to **System >  General Settings > IP2Proxy**. Insert the absolute path of the BIN database and save the changes.
8. Go to your website's Visitor tab. You should see a new tab **Proxy Details** available.

   

## How to import the IP2Proxy BIN file for usage
You should copy the BIN file into **/var/www/html/misc** folder (for default installation). If you customize the installation path, it should be the **misc** folder inside your root folder.

If you are using Matomo docker image, then you can use below command to copy the BIN into Matomo container.
```
sudo docker cp {your_local_bin_file_location} {your_matomo_container_name}:/var/www/html/misc
```

## FAQ

__How to I configure the plugin?__

Login as administrator, then go to System > General Settings > IP2Proxy.



__Where to download IP2Proxy database?__

You can download IP2Location database for free at [https://lite.ip2location.com](https://lite.ip2location.com/ip2proxy-lite) or commercial version from [https://www.ip2location.com](https://www.ip2location.com/databases/ip2proxy)



__Where to sign up ip2location.io Geolocation API Service?__

Sign for a free account at https://www.ip2location.io/



IPv4 BIN vs IPv6 BIN
====================

Use the IPv4 BIN file if you just need to query IPv4 addresses.

Use the IPv6 BIN file if you need to query **BOTH** IPv4 and IPv6 addresses.



## License

GPL v3 / fair use



## Support
Website: https://www.ip2location.com
Email: support@ip2location.com