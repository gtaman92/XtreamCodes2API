# XtreamCodes2API
Wrapper to allow continuation of service during the Xtream Codes downtime.

Current Functionality
---------------------
- Restart stream.
- Create stream.

How to Use
----------
- Place the php file here: `/home/xtreamcodes/iptv_xtream_codes/wwwdir/includes/restart_stream.php`
- Edit the MySQL connection details and authorisation password in the php file. Save it.
- Run the script: `http://IPADDRESS:PORT/includes/restart_stream.php?auth=PASSWORD&id=STREAM_ID`

Create a Stream
---------------
- Enter the MySQL database.
- Create a new stream under the `streams` table.
- Run the script to analyse the stream and start it.

Change a Stream Source
----------------------
- Enter the MySQL database.
- Find your stream in the `streams` table.
- Edit the `stream_source` column with your new sources. List format, URL encoded. E.g. ["http:\/\/website.com\/source.m3u8"]
- Run the script to analyse the stream and restart it with the new sources.

Restart a Stream
----------------
- Run the script to restart a stream. No changes to the database required.
